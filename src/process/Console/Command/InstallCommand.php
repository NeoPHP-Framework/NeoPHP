<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Command;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use NeoPHP\Process\Installer\Contract\InstallerInterface;

class InstallCommand extends AbstractCommand
{
    protected string $name = 'install';

    protected string $description = 'Generates the project files (public/, src/Kernel.php, config/, templates/...). Use --force to overwrite';

    public function __construct(protected InstallerInterface $installer, protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $projectDir = (string) $this->container->get('kernel.project_dir');
        $report = $this->installer->install($projectDir, (bool) $input->getOption('force', false));

        $output->writeln(sprintf('<title>Installing NeoPHP in</title> %s', $projectDir));
        $output->writeln();

        foreach ($report as $path => $status) {
            $style = match ($status) {
                InstallerInterface::STATUS_CREATED => 'success',
                InstallerInterface::STATUS_OVERWRITTEN, InstallerInterface::STATUS_UPDATED => 'comment',
                default => 'muted',
            };

            $output->writeln(sprintf('  <%1$s>%2$s</%1$s>  %3$s', $style, str_pad($status, 11), $path));
        }

        $output->writeln();

        if (($report['composer.json'] ?? null) === InstallerInterface::STATUS_UPDATED) {
            $output->writeln('<comment>composer.json was updated: run "composer dump-autoload".</comment>');
        }

        $output->writeln('<success>Done.</success> Start the server with: php bin/neo serve');

        return self::SUCCESS;
    }
}