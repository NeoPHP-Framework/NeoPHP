<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Orm\Provider\OrmProvider;
use NeoPHP\Package\Security\Maker\VoterMaker;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MakeVoterCommand extends AbstractCommand
{
    protected string $name = 'make:voter';

    protected string $description = 'Generates a voter in src/Security/Voter/. Usage: make:voter Post [--force]';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $name = $input->getArgument(0);

        if ($name === null || $name === '') {
            $output->writeln('<error>Missing voter name.</error> Usage: php bin/neo make:voter Post');

            return self::INVALID;
        }

        $root = (string) $this->container->get('kernel.root_path');
        $maker = new VoterMaker($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Security' . DIRECTORY_SEPARATOR . 'Voter', 'App\\Security\\Voter');

        try {
            [$class, $file, $attributes] = $maker->make($name, $this->subject($name), (bool) $input->getOption('force', false));
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));
        $short = substr($class, (int) strrpos($class, '\\') + 1);
        $output->writeln(sprintf('Attributes: <info>%s</info>.', implode(', ', $attributes)));
        $output->writeln(sprintf('Use them with <info>$this->denyAccessUnlessGranted(%s::EDIT, $subject)</info> in a controller or <info>is_granted(\'%s\', subject)</info> in a template.', $short, $attributes[1]));

        return self::SUCCESS;
    }

    protected function subject(string $name): ?string
    {
        $name = preg_replace('/Voter$/', '', trim(str_replace('/', '\\', $name), '\\'));
        $namespace = 'App\\Entity';

        if ($this->container->has(OrmProvider::CONFIG_ID)) {
            $namespace = (string) ($this->container->get(OrmProvider::CONFIG_ID)['entity']['namespace'] ?? $namespace);
        }

        $class = $namespace . '\\' . $name;

        return class_exists($class) ? $class : null;
    }
}