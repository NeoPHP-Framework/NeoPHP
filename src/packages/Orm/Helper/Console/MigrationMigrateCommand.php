<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Contract\MigrationInterface;
use NeoPHP\Package\Orm\Migration\Migrator;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MigrationMigrateCommand extends AbstractCommand
{
    protected string $name = 'migration:migrate';

    protected string $description = 'Executes the migrations not executed yet. Options: --dry-run';

    public function __construct(protected Migrator $migrator)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run', false);

        try {
            if ($this->migrator->getPending() === []) {
                $output->writeln('<comment>Already up to date: no migration to execute.</comment>');

                return self::SUCCESS;
            }

            $executed = $this->migrator->migrate($dryRun, static function (MigrationInterface $migration, string $direction, array $statements) use ($output, $dryRun): void {
                $output->writeln(sprintf('  <info>up</info>  Migration_%s %s<muted>(%d statement(s))</muted>', $migration->getVersion(), $migration->getDescription() !== '' ? $migration->getDescription() . ' ' : '', count($statements)));

                if ($dryRun) {
                    foreach ($statements as [$sql]) {
                        $output->writeln('      ' . $sql . ';');
                    }
                }
            });
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln($dryRun ? sprintf('<comment>Dry run: %d migration(s) not executed.</comment>', count($executed)) : sprintf('<success>%d migration(s) executed.</success>', count($executed)));

        return self::SUCCESS;
    }
}