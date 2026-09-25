<?php

declare(strict_types=1);

namespace NeoPHP\Component\Database\Helper\Console;

use NeoPHP\Component\Database\Contract\DatabaseInterface;
use NeoPHP\Component\Database\Exception\DatabaseException;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class DatabaseCreateCommand extends AbstractCommand
{
    protected string $name = 'database:create';

    protected string $description = 'Creates the configured database. Options: --connection=name, --if-not-exists';

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

            if (!($params['memory'] ?? false) && $driver->databaseExists($params)) {
                if ($input->getOption('if-not-exists', false)) {
                    $output->writeln(sprintf('<comment>The database %s already exists: skipped.</comment>', $label));

                    return self::SUCCESS;
                }

                $output->writeln(sprintf('<error>The database %s already exists.</error>', $label));

                return self::FAILURE;
            }

            if (!$driver->createDatabase($params)) {
                $output->writeln(sprintf('<comment>Nothing to create for the database %s.</comment>', $label));

                return self::SUCCESS;
            }
        } catch (DatabaseException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>Database %s created.</success>', $label));

        return self::SUCCESS;
    }
}