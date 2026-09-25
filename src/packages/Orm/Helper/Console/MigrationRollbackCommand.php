<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Contract\MigrationInterface;
use NeoPHP\Package\Orm\Migration\Migrator;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MigrationRollbackCommand extends AbstractCommand
{
    protected string $name = 'migration:rollback';

    protected string $description = 'Rolls back the last executed migrations (down). Options: --steps=1, --dry-run';

    public function __construct(protected Migrator $migrator)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $steps = max(1, (int) ($input->getOption('steps', '1') ?: 1));
        $dryRun = (bool) $input->getOption('dry-run', false);

        try {
            if ($this->migrator->getExecuted() === []) {
                $output->writeln('<comment>No migration to roll back.</comment>');

                return self::SUCCESS;
            }

            $rolledBack = $this->migrator->rollback($steps, $dryRun, static function (MigrationInterface $migration, string $direction, array $statements) use ($output, $dryRun): void {
                $output->writeln(sprintf('  <comment>down</comment>  Migration_%s %s<muted>(%d statement(s))</muted>', $migration->getVersion(), $migration->getDescription() !== '' ? $migration->getDescription() . ' ' : '', count($statements)));

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

        $output->writeln($dryRun ? sprintf('<comment>Dry run: %d migration(s) not rolled back.</comment>', count($rolledBack)) : sprintf('<success>%d migration(s) rolled back.</success>', count($rolledBack)));

        return self::SUCCESS;
    }
}