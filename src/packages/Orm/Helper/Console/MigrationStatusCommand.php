<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Migration\Migrator;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MigrationStatusCommand extends AbstractCommand
{
    protected string $name = 'migration:status';

    protected string $description = 'Lists the migrations and whether they are executed';

    public function __construct(protected Migrator $migrator)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        try {
            $status = $this->migrator->getStatus();
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<title>Migrations</title> %s (table %s)', $this->migrator->getDirectory(), $this->migrator->getTable()));

        if ($status === []) {
            $output->writeln('<comment>No migration found.</comment> Generate one with: php bin/neo make:migration');

            return self::SUCCESS;
        }

        $rows = [];
        $pending = 0;

        foreach ($status as $migration) {
            $state = match (true) {
                !$migration['available'] => 'missing file',
                $migration['executed_at'] !== null => 'executed',
                default => 'pending',
            };
            $pending += $state === 'pending' ? 1 : 0;
            $rows[] = [
                'Migration_' . $migration['version'],
                $migration['description'],
                $state,
                (string) ($migration['executed_at'] ?? ''),
                $migration['execution_time'] !== null ? $migration['execution_time'] . ' ms' : '',
            ];
        }

        $output->table(['Migration', 'Description', 'Status', 'Executed at', 'Time'], $rows);
        $output->writeln(sprintf('%d migration(s), %d pending.', count($status), $pending));

        return self::SUCCESS;
    }
}