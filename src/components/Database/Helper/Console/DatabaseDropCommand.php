<?php

declare(strict_types=1);

namespace NeoPHP\Component\Database\Helper\Console;

use NeoPHP\Component\Database\Contract\DatabaseInterface;
use NeoPHP\Component\Database\Exception\DatabaseException;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class DatabaseDropCommand extends AbstractCommand
{
    protected string $name = 'database:drop';

    protected string $description = 'Drops the configured database (requires --force). Options: --connection=name, --if-exists';

    public function __construct(protected DatabaseInterface $database)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $connection = $input->getOption('connection');
        $name = is_string($connection) && $connection !== '' ? $connection : $this->database->getDefaultConnectionName();

        try {
            $params = $this->database->getParams($name);
            $driver = $this->database->getDriver((string) $params['driver']);
            $label = sprintf('"%s" (%s, connection "%s")', $this->database->connection($name)->getDatabase() ?? '', $driver->getName(), $name);

            if (!$input->getOption('force', false)) {
                $output->writeln(sprintf('<comment>This will delete the database %s and all its data.</comment>', $label));
                $output->writeln('Run the command again with <info>--force</info> to confirm.');

                return self::INVALID;
            }

            $this->database->close($name);

            if ($params['memory'] ?? false) {
                $output->writeln(sprintf('<comment>Nothing to drop for the in-memory database %s.</comment>', $label));

                return self::SUCCESS;
            }

            if (!$driver->databaseExists($params)) {
                if ($input->getOption('if-exists', false)) {
                    $output->writeln(sprintf('<comment>The database %s does not exist: skipped.</comment>', $label));

                    return self::SUCCESS;
                }

                $output->writeln(sprintf('<error>The database %s does not exist.</error>', $label));

                return self::FAILURE;
            }

            $driver->dropDatabase($params);
        } catch (DatabaseException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>Database %s dropped.</success>', $label));

        return self::SUCCESS;
    }
}