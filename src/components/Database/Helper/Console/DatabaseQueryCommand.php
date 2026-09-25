<?php

declare(strict_types=1);

namespace NeoPHP\Component\Database\Helper\Console;

use NeoPHP\Component\Database\Contract\DatabaseInterface;
use NeoPHP\Component\Database\Exception\DatabaseException;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class DatabaseQueryCommand extends AbstractCommand
{
    public const MAX_WIDTH = 60;

    protected string $name = 'database:query';

    protected string $description = 'Executes a SQL query and displays the result. Usage: database:query "SELECT ..." [--connection=name]';

    public function __construct(protected DatabaseInterface $database)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $sql = trim(implode(' ', $input->getArguments()));

        if ($sql === '') {
            $output->writeln('<error>Missing SQL query.</error> Usage: php bin/neo database:query "SELECT * FROM user" [--connection=name]');

            return self::INVALID;
        }

        $connection = $input->getOption('connection');
        $name = is_string($connection) && $connection !== '' ? $connection : null;

        try {
            $result = $this->database->connection($name)->executeQuery($sql);

            if ($result->columnCount() === 0) {
                $output->writeln(sprintf('<success>Query executed.</success> %d row(s) affected.', $result->rowCount()));

                return self::SUCCESS;
            }

            $columns = $result->getColumnNames();
            $rows = array_map(fn (array $row): array => array_map(fn (mixed $value): string => $this->format($value), $row), $result->fetchAllNumeric());
        } catch (DatabaseException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        if ($rows === []) {
            $output->writeln('<comment>No rows.</comment>');

            return self::SUCCESS;
        }

        $output->table($columns, $rows);
        $output->writeln(sprintf('%d row(s)', count($rows)));

        return self::SUCCESS;
    }

    protected function format(mixed $value): string
    {
        $value = match (true) {
            $value === null => 'NULL',
            is_bool($value) => $value ? 'true' : 'false',
            is_resource($value) => (string) stream_get_contents($value),
            default => (string) $value,
        };

        $value = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $value);

        return mb_strlen($value) > self::MAX_WIDTH ? mb_substr($value, 0, self::MAX_WIDTH - 3) . '...' : $value;
    }
}