<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Contract\OrmInterface;
use NeoPHP\Package\Orm\Migration\MigrationGenerator;
use NeoPHP\Package\Orm\Migration\Migrator;
use NeoPHP\Package\Orm\Schema\SchemaTool;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MakeMigrationCommand extends AbstractCommand
{
    protected string $name = 'make:migration';

    protected string $description = 'Generates a migration (Migration_{hash}.php) from the differences between the entities and the database. Options: --empty, --description="..."';

    public function __construct(protected OrmInterface $orm, protected SchemaTool $schemaTool, protected Migrator $migrator, protected MigrationGenerator $generator)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $empty = (bool) $input->getOption('empty', false);
        $description = $input->getOption('description');
        $description = is_string($description) ? $description : '';

        try {
            $pending = $this->migrator->getPending();

            if ($pending !== [] && !$empty) {
                $output->writeln(sprintf('<error>%d migration(s) not executed yet.</error> Run <info>php bin/neo migration:migrate</info> first, then generate the new migration.', count($pending)));

                return self::FAILURE;
            }

            [$up, $down] = $empty ? [[], []] : $this->schemaTool->getMigrationSql();

            if ($up === [] && !$empty) {
                $output->writeln('<comment>No changes detected: the database is in sync with the entities.</comment>');

                return self::SUCCESS;
            }

            $file = $this->generator->generate($up, $down, $empty ? null : $this->orm->getPlatform()->getName(), $description);
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));

        if (!$empty) {
            $output->writeln(sprintf('%d SQL statement(s) in up(), %d in down().', count($up), count($down)));
        }

        $output->writeln('Review it, then run: <info>php bin/neo migration:migrate</info>');

        return self::SUCCESS;
    }
}