<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Exception\OrmException;
use NeoPHP\Package\Orm\Maker\EntityMaker;
use NeoPHP\Package\Orm\Maker\RepositoryMaker;
use NeoPHP\Process\Console\Attribute\AsCommand;
use NeoPHP\Process\Console\Contract\AbstractConsole;
use NeoPHP\Process\Console\Contract\InputInterface;
use NeoPHP\Process\Console\Contract\OutputInterface;
use NeoPHP\Process\Console\IO\InputArgument;

#[AsCommand(name: 'make:entity', description: 'Generates an entity and its repository')]
class MakeEntityCommand extends AbstractConsole
{
    public function __construct(protected EntityMaker $entityMaker, protected RepositoryMaker $repositoryMaker)
    {
    }

    protected function configure(InputInterface $input, OutputInterface $output): void
    {
        $input->addArgument('name', InputArgument::REQUIRED, 'The entity name (e.g. Post, Blog/Post)');
        $input->addArgument('fields', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The fields: name:type[:length][?] or name:Relation:Target[:mappedBy]');
        $this->setHelp(implode("\n", [
            'Types: ' . implode(', ', array_keys(EntityMaker::TYPES)) . ', enum:App\\Enum\\Status',
            'Relations: name:ManyToOne:Target, name:OneToOne:Target, name:OneToMany:Target[:mappedBy], name:ManyToMany:Target',
            'A trailing "?" makes the field nullable. Existing files are only replaced with --force.',
        ]));
        $this->addExample('make:entity Category name:string:80');
        $this->addExample('make:entity Post title:string:120 content:text? publishedAt:datetime_immutable? category:ManyToOne:Category');
        $this->addExample('make:entity Post title:string --force');
    }

    protected function do(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $force = (bool) $input->getOption('force');

        try {
            $fields = $this->entityMaker->parseFields((array) $input->getArgument('fields'));
            [$entityClass] = $this->entityMaker->resolve($name);
            $repositoryClass = $this->repositoryMaker->getRepositoryClass($entityClass);
            [, $entityFile] = $this->entityMaker->make($name, $fields, $repositoryClass, $force);
            [, $repositoryFile] = $this->repositoryMaker->make($entityClass, $force);
        } catch (OrmException $exception) {
            $output->error($exception->getMessage());

            return self::FAILURE;
        }

        $output->writeln(sprintf('  <success>created</success>  %s', $entityFile));
        $output->writeln(sprintf('  <success>created</success>  %s', $repositoryFile));

        foreach ($fields as $field) {
            if (($field['relation'] ?? null) === 'OneToMany') {
                $output->warning(sprintf('%s::$%s is mapped by %s::$%s: add the ManyToOne side in %s.', $entityClass, $field['name'], $field['target'], $field['mappedBy'] ?? lcfirst(substr($entityClass, (int) strrpos($entityClass, '\\') + 1)), $field['target']));
            }
        }

        $output->success(sprintf('Entity %s created with %d field(s).', $entityClass, count($fields)));
        $output->text('Next: <info>php bin/neo make:migration</info> then <info>php bin/neo migration:migrate</info>');

        return self::SUCCESS;
    }
}