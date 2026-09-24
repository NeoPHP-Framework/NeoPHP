<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Loader;

use NeoPHP\Component\Routing\Exception\RoutingException;
use NeoPHP\Component\Routing\Route\Route;
use NeoPHP\Component\Routing\Route\RouteCollection;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class YamlRouteLoader
{
    private const ROUTE_KEYS = ['path', 'controller', 'methods', 'requirements', 'defaults', 'options'];
    private const IMPORT_KEYS = ['resource', 'prefix', 'name_prefix', 'requirements', 'defaults', 'options', 'methods'];

    private array $loading = [];

    protected mixed $resolver;

    public function __construct(protected YamlInterface $yaml, ?callable $resolver = null)
    {
        $this->resolver = $resolver;
    }

    public function load(string $file): RouteCollection
    {
        $real = realpath($file);

        if ($real === false) {
            throw new RoutingException(sprintf('The routes file "%s" does not exist.', $file));
        }

        if (isset($this->loading[$real])) {
            throw new RoutingException(sprintf('Circular import detected for routes file "%s".', $real));
        }

        $this->loading[$real] = true;

        try {
            $definitions = $this->yaml->parseFile($real) ?? [];

            if ($this->resolver !== null && is_array($definitions)) {
                $definitions = ($this->resolver)($definitions);
            }

            if (!is_array($definitions)) {
                throw new RoutingException(sprintf('The routes file "%s" must contain a mapping of routes.', $real));
            }

            $collection = new RouteCollection();

            foreach ($definitions as $name => $definition) {
                $name = (string) $name;

                if (!is_array($definition)) {
                    throw new RoutingException(sprintf('The definition of route "%s" in "%s" must be a mapping.', $name, $real));
                }

                if (isset($definition['resource'])) {
                    $collection->addCollection($this->import($name, $definition, $real));
                } else {
                    $collection->add($this->createRoute($name, $definition, $real));
                }
            }

            return $collection;
        } finally {
            unset($this->loading[$real]);
        }
    }

    private function createRoute(string $name, array $definition, string $file): Route
    {
        $this->assertKeys($name, $definition, self::ROUTE_KEYS, $file);

        if (!isset($definition['path']) || !is_string($definition['path'])) {
            throw new RoutingException(sprintf('The route "%s" in "%s" must define a "path".', $name, $file));
        }

        if (!isset($definition['controller'])) {
            throw new RoutingException(sprintf('The route "%s" in "%s" must define a "controller".', $name, $file));
        }

        return new Route(
            $name,
            $definition['path'],
            $definition['controller'],
            $this->methods($definition['methods'] ?? []),
            $this->map($definition['requirements'] ?? [], 'requirements', $name, $file),
            $this->map($definition['defaults'] ?? [], 'defaults', $name, $file),
            $this->map($definition['options'] ?? [], 'options', $name, $file),
        );
    }

    private function import(string $name, array $definition, string $file): RouteCollection
    {
        $this->assertKeys($name, $definition, self::IMPORT_KEYS, $file);

        $resource = (string) $definition['resource'];
        $path = preg_match('#^([a-zA-Z]:)?[/\\\\]#', $resource) === 1 ? $resource : dirname($file) . DIRECTORY_SEPARATOR . $resource;
        $imported = $this->load($path);

        $prefix = trim((string) ($definition['prefix'] ?? ''), '/');
        $namePrefix = (string) ($definition['name_prefix'] ?? '');
        $requirements = $this->map($definition['requirements'] ?? [], 'requirements', $name, $file);
        $defaults = $this->map($definition['defaults'] ?? [], 'defaults', $name, $file);
        $options = $this->map($definition['options'] ?? [], 'options', $name, $file);
        $methods = $this->methods($definition['methods'] ?? []);

        $collection = new RouteCollection();

        foreach ($imported as $route) {
            $collection->add(new Route(
                $namePrefix . $route->getName(),
                ($prefix !== '' ? '/' . $prefix : '') . $route->getPath(),
                $route->getController(),
                $route->getMethods() !== [] ? $route->getMethods() : $methods,
                $route->getRequirements() + $requirements,
                $route->getDefaults() + $defaults,
                $route->getOptions() + $options,
            ));
        }

        return $collection;
    }

    private function methods(mixed $methods): array
    {
        if (is_string($methods)) {
            $methods = preg_split('/\s*[|,]\s*/', trim($methods), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_map(static fn (mixed $m): string => strtoupper((string) $m), (array) $methods));
    }

    private function map(mixed $value, string $key, string $name, string $file): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new RoutingException(sprintf('The "%s" of route "%s" in "%s" must be a mapping.', $key, $name, $file));
        }

        return $value;
    }

    private function assertKeys(string $name, array $definition, array $allowed, string $file): void
    {
        $unknown = array_diff(array_keys($definition), $allowed);

        if ($unknown !== []) {
            throw new RoutingException(sprintf(
                'Unknown key(s) "%s" for route "%s" in "%s". Allowed keys: "%s".',
                implode('", "', $unknown),
                $name,
                $file,
                implode('", "', $allowed),
            ));
        }
    }
}