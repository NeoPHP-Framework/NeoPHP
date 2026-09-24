<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Exception\ConsoleException;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use ReflectionClass;
use Throwable;

abstract class AbstractConsole implements ConsoleInterface
{
    protected array $commands = [];

    protected ?array $resolved = null;

    public function __construct(protected ?ContainerInterface $container = null, protected string $version = '')
    {
    }

    public function add(CommandInterface|string $command): static
    {
        $this->commands[] = $command;
        $this->resolved = null;

        return $this;
    }

    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $resolved = [];

        foreach ($this->commands as $command) {
            $command = $this->instantiate($command);
            $resolved[$command->getName()] = $command;
        }

        ksort($resolved);

        return $this->resolved = $resolved;
    }

    public function run(array $argv, ?Output $output = null): int
    {
        $output ??= new Output();
        $tokens = array_values(array_slice($argv, 1));
        $name = $tokens[0] ?? 'list';

        try {
            if (in_array($name, ['list', 'help', '--help', '-h'], true)) {
                $this->renderList($output);

                return CommandInterface::SUCCESS;
            }

            $command = $this->find($name);

            if ($command === null) {
                $output->writeln(sprintf('<error>Command "%s" is not defined.</error>', $name));
                $this->renderList($output);

                return CommandInterface::INVALID;
            }

            return $command->execute(Input::fromTokens(array_slice($tokens, 1)), $output);
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>[%s] %s</error>', $exception::class, $exception->getMessage()));
            $output->writeln(sprintf('<muted>%s:%d</muted>', $exception->getFile(), $exception->getLine()));

            return CommandInterface::FAILURE;
        }
    }

    public function find(string $name): ?CommandInterface
    {
        foreach ($this->commands as $command) {
            if ($this->nameOf($command) === $name) {
                return $this->instantiate($command);
            }
        }

        return null;
    }

    protected function nameOf(CommandInterface|string $command): string
    {
        if ($command instanceof CommandInterface) {
            return $command->getName();
        }

        if (!class_exists($command)) {
            throw new ConsoleException('The command class "{command}" does not exist.', 0, null, ['command' => $command]);
        }

        $name = (new ReflectionClass($command))->getDefaultProperties()['name'] ?? '';

        return is_string($name) && $name !== '' ? $name : $this->instantiate($command)->getName();
    }

    protected function instantiate(CommandInterface|string $command): CommandInterface
    {
        if (is_string($command)) {
            $command = $this->container !== null ? $this->container->get($command) : new $command();
        }

        if (!$command instanceof CommandInterface) {
            throw new ConsoleException('"{command}" must implement {interface}.', 0, null, [
                'command' => get_debug_type($command),
                'interface' => CommandInterface::class,
            ]);
        }

        return $command;
    }

    protected function renderList(Output $output): void
    {
        $output->writeln(sprintf('<title>NeoPHP</title> %s', $this->version));
        $output->writeln();
        $output->writeln('<comment>Usage:</comment> php bin/neo <command> [arguments] [--options]');
        $output->writeln();
        $output->writeln('<comment>Available commands:</comment>');

        $rows = ['list' => 'Lists the available commands'];

        foreach ($this->commands as $command) {
            try {
                $instance = $this->instantiate($command);
                $rows[$instance->getName()] = $instance->getDescription();
            } catch (Throwable $exception) {
                $rows[$this->nameOf($command)] = sprintf('<error>unavailable: %s</error>', $exception->getMessage());
            }
        }

        ksort($rows);
        $width = max(array_map('strlen', array_keys($rows)));

        foreach ($rows as $name => $description) {
            $output->writeln(sprintf('  <info>%s</info>  %s', str_pad($name, $width), $description));
        }
    }
}