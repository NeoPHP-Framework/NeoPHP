<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Console;

use NeoPHP\Package\Orm\Exception\OrmException;
use NeoPHP\Package\Orm\Maker\EntityMaker;
use NeoPHP\Package\Orm\Maker\RepositoryMaker;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class MakeRepositoryCommand extends AbstractCommand
{
    protected string $name = 'make:repository';

    protected string $description = 'Generates the repository of an entity. Usage: make:repository Post [--force]';

    public function __construct(protected EntityMaker $entityMaker, protected RepositoryMaker $repositoryMaker)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);

        if ($name === null || $name === '') {
            $output->writeln('<error>Missing entity name.</error> Usage: php bin/neo make:repository Post');

            return self::INVALID;
        }

        try {
            [$entityClass] = $this->entityMaker->resolve($name);

            if (!class_exists($entityClass)) {
                $output->writeln(sprintf('<error>The entity %s does not exist.</error> Create it with: php bin/neo make:entity %s', $entityClass, $name));

                return self::FAILURE;
            }

            [$repositoryClass, $file] = $this->repositoryMaker->make($entityClass, (bool) $input->getOption('force', false));
        } catch (OrmException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));
        $output->writeln(sprintf('%s is used by getRepository(%s::class) and can be injected in controllers and services.', $repositoryClass, substr($entityClass, (int) strrpos($entityClass, '\\') + 1)));

        return self::SUCCESS;
    }
}