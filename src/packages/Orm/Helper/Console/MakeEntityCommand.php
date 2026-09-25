<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Exception\OrmException;
use NeoPHP\Package\Orm\Maker\EntityMaker;
use NeoPHP\Package\Orm\Maker\RepositoryMaker;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class MakeEntityCommand extends AbstractCommand
{
    protected string $name = 'make:entity';

    protected string $description = 'Generates an entity and its repository. Usage: make:entity Post title:string content:text? category:ManyToOne:Category [--force]';

    public function __construct(protected EntityMaker $entityMaker, protected RepositoryMaker $repositoryMaker)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $arguments = $input->getArguments();
        $name = array_shift($arguments);

        if ($name === null || $name === '') {
            $output->writeln('<error>Missing entity name.</error> Usage: php bin/neo make:entity Post title:string:120 content:text? publishedAt:datetime_immutable? category:ManyToOne:Category');
            $output->writeln('Types: ' . implode(', ', array_keys(EntityMaker::TYPES)) . ', enum:App\\Enum\\Status');
            $output->writeln('Relations: name:ManyToOne:Target, name:OneToOne:Target, name:OneToMany:Target[:mappedBy], name:ManyToMany:Target. A trailing "?" makes the field nullable.');

            return self::INVALID;
        }

        $force = (bool) $input->getOption('force', false);

        try {
            $fields = $this->entityMaker->parseFields($arguments);
            [$entityClass] = $this->entityMaker->resolve($name);
            $repositoryClass = $this->repositoryMaker->getRepositoryClass($entityClass);
            [, $entityFile] = $this->entityMaker->make($name, $fields, $repositoryClass, $force);
            [, $repositoryFile] = $this->repositoryMaker->make($entityClass, $force);
        } catch (OrmException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $entityFile));
        $output->writeln(sprintf('<success>created</success>  %s', $repositoryFile));

        foreach ($fields as $field) {
            if (($field['relation'] ?? null) === 'OneToMany') {
                $output->writeln(sprintf('<comment>%s::$%s is mapped by %s::$%s: add the ManyToOne side in %s.</comment>', $entityClass, $field['name'], $field['target'], $field['mappedBy'] ?? lcfirst(substr($entityClass, (int) strrpos($entityClass, '\\') + 1)), $field['target']));
            }
        }

        $output->writeln('Next: <info>php bin/neo make:migration</info> then <info>php bin/neo migration:migrate</info>');

        return self::SUCCESS;
    }
}