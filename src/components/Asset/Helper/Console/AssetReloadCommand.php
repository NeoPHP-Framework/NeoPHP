<?php

declare(strict_types=1);

namespace NeoPHP\Component\Asset\Helper\Console;

use NeoPHP\Component\Asset\Contract\AssetInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class AssetReloadCommand extends AbstractCommand
{
    protected string $name = 'asset:reload';

    protected string $description = 'Compiles assets/ into public/builds/ and rebuilds the manifest. Use --minify to minify CSS and JS';

    public function __construct(protected AssetInterface $asset)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $minify = (bool) $input->getOption('minify', false);
        $built = $this->asset->reload($minify);

        $output->writeln(sprintf('<title>Compiling assets</title> %s -> %s%s', $this->asset->getSourcePath(), $this->asset->getBuildPath(), $minify ? ' (minified)' : ''));
        $output->writeln();

        if ($built === []) {
            $output->writeln('<comment>No asset found.</comment>');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($built as $path => $url) {
            $rows[] = [$path, $url];
        }

        $output->table(['Asset', 'Build'], $rows);
        $output->writeln();
        $output->writeln(sprintf('<success>%d asset(s) compiled.</success> Manifest: %s', count($built), $this->asset->getManifest()->getFile()));

        return self::SUCCESS;
    }
}