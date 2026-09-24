<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Route;

class RouteCollection implements \IteratorAggregate, \Countable
{
    private array $routes = [];

    public function add(Route $route): self
    {
        unset($this->routes[$route->getName()]);
        $this->routes[$route->getName()] = $route;

        return $this;
    }

    public function addCollection(self $collection): self
    {
        foreach ($collection as $route) {
            $this->add($route);
        }

        return $this;
    }

    public function get(string $name): ?Route
    {
        return $this->routes[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->routes[$name]);
    }

    public function remove(string $name): void
    {
        unset($this->routes[$name]);
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->routes);
    }

    public function count(): int
    {
        return count($this->routes);
    }
}