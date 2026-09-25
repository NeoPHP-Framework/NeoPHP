<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Orm\Provider\OrmProvider;
use NeoPHP\Package\Security\Maker\UserMaker;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MakeUserCommand extends AbstractCommand
{
    protected string $name = 'make:user';

    protected string $description = 'Generates a User entity and its repository. Usage: make:user [User] [--property=email] [--force]';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(0, 'User') ?? 'User';
        $property = $input->getOption('property', 'email');
        $property = is_string($property) && $property !== '' ? $property : 'email';
        $root = (string) $this->container->get('kernel.root_path');
        $orm = $this->container->has(OrmProvider::CONFIG_ID) ? $this->container->get(OrmProvider::CONFIG_ID) : [];
        $maker = new UserMaker(
            (string) ($orm['entity']['path'] ?? $root . '/src/Entity'),
            (string) ($orm['entity']['namespace'] ?? 'App\\Entity'),
            (string) ($orm['repository']['path'] ?? $root . '/src/Repository'),
            (string) ($orm['repository']['namespace'] ?? 'App\\Repository'),
        );

        try {
            [$class, $file, , $repositoryFile] = $maker->make($name, $property, (bool) $input->getOption('force', false));
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));
        $output->writeln(sprintf('<success>created</success>  %s', $repositoryFile));
        $output->writeln('');
        $output->writeln('Next steps:');
        $output->writeln('  1. Create the table: <info>php bin/neo make:migration</info> then <info>php bin/neo migration:migrate</info>');
        $output->writeln('  2. Use it in <info>config/packages/security.yaml</info>:');
        $output->writeln('       providers:');
        $output->writeln('         users:');
        $output->writeln('           entity:');
        $output->writeln(sprintf('             class: %s', $class));
        $output->writeln(sprintf('             property: %s', $property));
        $output->writeln('  3. Hash a password: <info>php bin/neo security:hash-password secret</info>');

        return self::SUCCESS;
    }
}