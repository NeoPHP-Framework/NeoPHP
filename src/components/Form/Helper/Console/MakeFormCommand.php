<?php

declare(strict_types=1);

namespace NeoPHP\Component\Form\Helper\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Form\Maker\FormMaker;
use NeoPHP\Package\Orm\Contract\OrmInterface;
use NeoPHP\Package\Orm\Provider\OrmProvider;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MakeFormCommand extends AbstractCommand
{
    protected string $name = 'make:form';

    protected string $description = 'Generates a form class in src/Form/. Usage: make:form Post [Entity] [--force]';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);

        if ($name === null || $name === '') {
            $output->writeln('<error>Missing form name.</error> Usage: php bin/neo make:form Post [Entity] (without entity: a form working with an array)');

            return self::INVALID;
        }

        $entity = $input->getArgument(1);
        $root = (string) $this->container->get('kernel.root_path');
        $orm = $this->container->has(OrmInterface::class) ? $this->container->get(OrmInterface::class) : null;
        $maker = new FormMaker($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Form', 'App\\Form', $orm);

        try {
            $entityClass = null;

            if ($entity !== null && $entity !== '') {
                $entityClass = $this->resolveEntity($entity);

                if ($entityClass === null) {
                    $output->writeln(sprintf('<error>The entity "%s" does not exist.</error>', $entity));

                    return self::FAILURE;
                }
            }

            [$class, $file, $fields] = $maker->make($name, $entityClass, (bool) $input->getOption('force', false));
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));
        $output->writeln(sprintf('%d field(s)%s. Use it in a controller: <info>$form = $this->createForm(%s::class%s);</info>', count($fields), $entityClass !== null ? ' from ' . $entityClass : '', substr($class, (int) strrpos($class, '\\') + 1), $entityClass !== null ? ', $entity' : ''));

        return self::SUCCESS;
    }

    protected function resolveEntity(string $entity): ?string
    {
        $entity = ltrim(str_replace('/', '\\', $entity), '\\');
        $namespace = 'App\\Entity';

        if ($this->container->has(OrmProvider::CONFIG_ID)) {
            $namespace = (string) ($this->container->get(OrmProvider::CONFIG_ID)['entity']['namespace'] ?? $namespace);
        }

        foreach ([$entity, $namespace . '\\' . $entity] as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}