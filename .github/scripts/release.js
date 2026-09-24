const TYPES = {
    feat: 'Features',
    fix: 'Bug fixes',
    perf: 'Performance',
    refactor: 'Refactoring',
    revert: 'Reverts',
    docs: 'Documentation',
    test: 'Tests',
    build: 'Build',
    ci: 'CI/CD',
    style: 'Style',
    chore: 'Chores',
};

const MINOR_TYPES = ['feat'];
const PATCH_TYPES = ['fix', 'perf', 'refactor', 'revert'];
const COMMIT_PATTERN = /^(\w+)(?:\(([^)]+)\))?(!)?:\s*(.+)$/;
const TAG_PATTERN = /^v(\d+)\.(\d+)\.(\d+)$/;
const VERSION_BRANCH_PATTERN = /^v(\d+)\.x$/i;
const DEV_BRANCH = 'dev';

function parseCommit(message) {
    const subject = message.split('\n')[0].trim();
    const match = subject.match(COMMIT_PATTERN);

    if (!match) {
        return { type: 'other', scope: null, subject, breaking: false };
    }

    return {
        type: match[1].toLowerCase(),
        scope: match[2] || null,
        subject: match[4].trim(),
        breaking: Boolean(match[3]) || /BREAKING CHANGE/.test(message),
    };
}

function isMergeCommit(message) {
    return /^Merge (pull request|branch|remote-tracking branch)/.test(message);
}

function compareVersions(a, b) {
    return a.major - b.major || a.minor - b.minor || a.patch - b.patch;
}

function formatVersion(version) {
    return `v${version.major}.${version.minor}.${version.patch}`;
}

function detectBump(messages, labels) {
    if (labels.includes('release:none')) {
        return null;
    }

    if (labels.includes('release:minor')) {
        return 'minor';
    }

    if (labels.includes('release:patch')) {
        return 'patch';
    }

    const commits = messages.filter((message) => !isMergeCommit(message)).map(parseCommit);

    if (commits.some((commit) => MINOR_TYPES.includes(commit.type) || commit.breaking)) {
        return 'minor';
    }

    if (commits.some((commit) => PATCH_TYPES.includes(commit.type) || !TYPES[commit.type])) {
        return 'patch';
    }

    return null;
}

function nextVersion(current, bump) {
    if (bump === 'minor') {
        return { major: current.major, minor: current.minor + 1, patch: 0 };
    }

    return { major: current.major, minor: current.minor, patch: current.patch + 1 };
}

function buildChangelog({ tag, previousTag, commits, owner, repo, docsUrl, date }) {
    const groups = {};

    for (const item of commits) {
        const message = item.commit.message;

        if (isMergeCommit(message)) {
            continue;
        }

        const commit = parseCommit(message);
        const group = TYPES[commit.type] ? commit.type : 'other';
        const scope = commit.scope ? `**${commit.scope}**: ` : '';

        groups[group] = groups[group] || [];
        groups[group].push(`- ${scope}${commit.subject} (${item.sha.substring(0, 7)})`);
    }

    const lines = [`## ${tag} (${date})`, ''];
    const order = [...Object.keys(TYPES), 'other'];
    let hasEntries = false;

    for (const type of order) {
        if (!groups[type]) {
            continue;
        }

        hasEntries = true;
        lines.push(`### ${TYPES[type] || 'Other changes'}`, '', ...groups[type], '');
    }

    if (!hasEntries) {
        lines.push('No notable changes.', '');
    }

    if (docsUrl) {
        lines.push(`**Documentation**: ${docsUrl}`, '');
    }

    if (previousTag) {
        lines.push(`**Full changelog**: https://github.com/${owner}/${repo}/compare/${previousTag}...${tag}`);
    }

    return lines.join('\n').trim() + '\n';
}

async function listVersions(github, owner, repo) {
    const tags = await github.paginate(github.rest.repos.listTags, { owner, repo, per_page: 100 });

    return tags
        .map((tag) => tag.name.match(TAG_PATTERN))
        .filter(Boolean)
        .map((match) => ({ major: Number(match[1]), minor: Number(match[2]), patch: Number(match[3]) }))
        .sort(compareVersions);
}

async function listCommits(github, owner, repo, base, head) {
    if (!base) {
        const commits = await github.paginate(github.rest.repos.listCommits, { owner, repo, sha: head, per_page: 100 });

        return commits.reverse();
    }

    return github.paginate(
        github.rest.repos.compareCommitsWithBasehead,
        { owner, repo, basehead: `${base}...${head}`, per_page: 100 },
        (response) => response.data.commits,
    );
}

async function branchExists(github, owner, repo, branch) {
    try {
        await github.rest.repos.getBranch({ owner, repo, branch });

        return true;
    } catch (error) {
        if (error.status === 404) {
            return false;
        }

        throw error;
    }
}

async function findDocsUrl(github, owner, repo, major, sha) {
    const path = `docs/v${major}.x/README.md`;

    try {
        await github.rest.repos.getContent({ owner, repo, path, ref: sha });

        return `https://github.com/${owner}/${repo}/blob/v${major}.x/${path}`;
    } catch (error) {
        if (error.status === 404) {
            return null;
        }

        throw error;
    }
}

async function publishRelease({ github, core, owner, repo, version, previous, sha, versions }) {
    const tag = formatVersion(version);
    const previousTag = previous ? formatVersion(previous) : null;
    const commits = await listCommits(github, owner, repo, previousTag, sha);
    const docsUrl = await findDocsUrl(github, owner, repo, version.major, sha);
    const date = new Date().toISOString().substring(0, 10);
    const isLatest = versions.every((existing) => compareVersions(version, existing) > 0);

    const body = buildChangelog({ tag, previousTag, commits, owner, repo, docsUrl, date });

    await github.rest.repos.createRelease({
        owner,
        repo,
        tag_name: tag,
        target_commitish: sha,
        name: tag,
        body,
        make_latest: isLatest ? 'true' : 'false',
    });

    core.notice(`Release ${tag} created on ${sha.substring(0, 7)}.`);
    core.setOutput('version', tag);

    return tag;
}

async function releaseMajor({ github, context, core }) {
    const { owner, repo } = context.repo;
    const sha = context.payload.pull_request.merge_commit_sha;
    const versions = await listVersions(github, owner, repo);
    const major = versions.length ? Math.max(...versions.map((version) => version.major)) + 1 : 1;
    const branch = `v${major}.x`;

    if (await branchExists(github, owner, repo, branch)) {
        core.setFailed(`The branch ${branch} already exists.`);

        return;
    }

    await github.rest.git.createRef({ owner, repo, ref: `refs/heads/${branch}`, sha });
    core.notice(`Branch ${branch} created on ${sha.substring(0, 7)}.`);

    await publishRelease({
        github,
        core,
        owner,
        repo,
        version: { major, minor: 0, patch: 0 },
        previous: versions.length ? versions[versions.length - 1] : null,
        sha,
        versions,
    });
}

async function releaseVersion({ github, context, core }) {
    const { owner, repo } = context.repo;
    const pullRequest = context.payload.pull_request;
    const match = pullRequest.base.ref.match(VERSION_BRANCH_PATTERN);

    if (!match) {
        core.info(`${pullRequest.base.ref} is not a version branch.`);

        return;
    }

    const major = Number(match[1]);
    const sha = pullRequest.merge_commit_sha;
    const commits = await github.paginate(github.rest.pulls.listCommits, {
        owner,
        repo,
        pull_number: pullRequest.number,
        per_page: 100,
    });

    const messages = [pullRequest.title, ...commits.map((commit) => commit.commit.message)];
    const labels = pullRequest.labels.map((label) => label.name);
    const bump = detectBump(messages, labels);

    if (!bump) {
        core.notice('No release: only docs, tests, CI or chores were merged.');

        return;
    }

    const versions = await listVersions(github, owner, repo);
    const branchVersions = versions.filter((version) => version.major === major);
    const current = branchVersions.length ? branchVersions[branchVersions.length - 1] : null;
    const version = current ? nextVersion(current, bump) : { major, minor: 0, patch: 0 };

    await publishRelease({ github, core, owner, repo, version, previous: current, sha, versions });
}

async function backMerge({ github, context, core }) {
    const { owner, repo } = context.repo;
    const branch = context.ref.replace('refs/heads/', '');

    if (!VERSION_BRANCH_PATTERN.test(branch)) {
        core.info(`${branch} is not a version branch.`);

        return;
    }

    const comparison = await github.rest.repos.compareCommitsWithBasehead({
        owner,
        repo,
        basehead: `${DEV_BRANCH}...${branch}`,
    });

    if (comparison.data.ahead_by === 0) {
        core.info(`${DEV_BRANCH} already contains every commit of ${branch}.`);

        return;
    }

    const existing = await github.rest.pulls.list({
        owner,
        repo,
        state: 'open',
        head: `${owner}:${branch}`,
        base: DEV_BRANCH,
    });

    if (existing.data.length) {
        core.notice(`Back-merge pull request already open: ${existing.data[0].html_url}`);

        return;
    }

    const created = await github.rest.pulls.create({
        owner,
        repo,
        head: branch,
        base: DEV_BRANCH,
        title: `chore(release): back-merge ${branch} into ${DEV_BRANCH}`,
        body: [
            `Automatic back-merge of **${branch}** into **${DEV_BRANCH}** (${comparison.data.ahead_by} commit(s)).`,
            '',
            'Merge it with a **merge commit** (no squash, no rebase) to keep the history of both branches aligned.',
        ].join('\n'),
    });

    core.notice(`Back-merge pull request created: ${created.data.html_url}`);
}

module.exports = {
    releaseMajor,
    releaseVersion,
    backMerge,
    detectBump,
    nextVersion,
    parseCommit,
    buildChangelog,
};