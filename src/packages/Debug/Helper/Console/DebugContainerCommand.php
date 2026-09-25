<?php

declare(strict_types=1);

namespace NeoPHP\Package\Debug\Helper\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Debug\Contract\DebugInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

class DebugContainerCommand extends AbstractCommand
{
    protected string $name = 'debug:container';

    protected string $description = 'Lists the services of the container or shows one of them. Usage: debug:container [filter|id] [--dump] [--parameters]';

    public function __construct(protected ContainerInterface $container, protected DebugInterface $debug)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $search = (string) ($input->getArgument(0) ?? '');
        $definitions = $this->container->getDefinitions();
        $aliases = $this->container->getAliases();

        if ($search !== '' && (isset($definitions[$search]) || isset($aliases[$search]))) {
            return $this->show($search, $definitions, $aliases, (bool) $input->getOption('dump', false), $output);
        }

        $parameters = (bool) $input->getOption('parameters', false);
        $rows = [];

        foreach ($definitions as $id => $definition) {
            if (($definition['kind'] === 'parameter') !== $parameters) {
                continue;
            }

            $rows[] = [(string) $id, $definition['kind'], $parameters ? $this->scalar($this->container->get((string) $id)) : (string) ($definition['class'] ?? $definition['concrete']), $definition['resolved'] ? 'yes' : 'no'];
        }

        if (!$parameters) {
            foreach ($aliases as $alias => $target) {
                $rows[] = [(string) $alias, 'alias', '@' . $target, ''];
            }
        }

        if ($search !== '') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => stripos($row[0] . ' ' . $row[2], $search) !== false));
        }

        if ($rows === []) {
            $output->writeln('<comment>No service found.</comment>');

            return self::SUCCESS;
        }

        usort($rows, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));
        $output->table(['Id', 'Kind', $parameters ? 'Value' : 'Class', 'Resolved'], $rows);
        $output->writeln(sprintf('%d result(s). Show one with <info>php bin/neo debug:container <id> [--dump]</info>.', count($rows)));

        return self::SUCCESS;
    }

    protected function show(string $id, array $definitions, array $aliases, bool $dump, Output $output): int
    {
        $target = $id;
        $seen = [];

        while (isset($aliases[$target]) && !isset($seen[$target])) {
            $seen[$target] = true;
            $target = $aliases[$target];
        }

        $definition = $definitions[$target] ?? ['kind' => 'autowired', 'concrete' => $target, 'class' => null, 'resolved' => false];
        $class = $definition['class'] ?? ($definition['concrete'] !== 'closure' && class_exists((string) $definition['concrete']) ? (string) $definition['concrete'] : null);
        $rows = [
            ['Id', $id],
            ['Service', $target !== $id ? $target . ' (alias)' : $target],
            ['Kind', $definition['kind']],
            ['Definition', (string) $definition['concrete']],
            ['Class', (string) ($class ?? ($definition['concrete'] === 'closure' ? 'built by a closure (not resolved yet)' : '?'))],
            ['Resolved', $definition['resolved'] ? 'yes' : 'no'],
            ['Aliases', implode(', ', array_keys(array_filter($aliases, static fn (string $alias): bool => $alias === $target))) ?: '-'],
        ];

        if ($definition['kind'] === 'parameter') {
            $rows[] = ['Value', $this->scalar($this->container->get($target))];
        } elseif ($class !== null && class_exists($class)) {
            $rows[] = ['Arguments', $this->arguments($class)];
        }

        $output->table(['Property', 'Value'], $rows);

        if ($dump) {
            try {
                $colors = getenv('NO_COLOR') === false && defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
                $output->write($this->debug->toText($this->container->get($target), $target, $colors));
            } catch (Throwable $exception) {
                $output->writeln(sprintf('<error>Unable to resolve "%s": %s</error>', $target, $exception->getMessage()));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    protected function arguments(string $class): string
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return '-';
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $arguments[] = trim(($type instanceof ReflectionNamedType ? $type->getName() : (string) $type) . ' $' . $parameter->getName() . ($parameter->isDefaultValueAvailable() ? ' = ' . $this->scalar($parameter->getDefaultValue()) : ''));
        }

        return implode(', ', $arguments);
    }

    protected function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            is_array($value) => strlen($json = (string) json_encode($value, JSON_UNESCAPED_SLASHES)) > 80 ? substr($json, 0, 77) . '...' : $json,
            default => get_debug_type($value),
        };
    }
}