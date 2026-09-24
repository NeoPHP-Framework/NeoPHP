<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Contract;

use NeoPHP\Component\Routing\Exception\MethodNotAllowedException;
use NeoPHP\Component\Routing\Exception\RouteNotDefinedException;
use NeoPHP\Component\Routing\Exception\RouteNotFoundException;
use NeoPHP\Component\Routing\Route\Route;
use NeoPHP\Component\Routing\Route\RouteCollection;
use NeoPHP\Component\Routing\Route\RouteMatch;

abstract class AbstractRouting implements RoutingInterface
{
    protected RouteCollection $routes;

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? new RouteCollection();
    }

    public function match(string $method, string $path): RouteMatch
    {
        $method = strtoupper($method);
        $path = Route::normalizePath($path);
        $allowed = [];

        foreach ($this->routes as $route) {
            $parameters = $route->match($path);

            if ($parameters === null) {
                continue;
            }

            if (!$route->allowsMethod($method)) {
                array_push($allowed, ...$route->getMethods());
                continue;
            }

            return new RouteMatch($route, $parameters);
        }

        if ($allowed !== []) {
            $allowed = array_values(array_unique($allowed));

            throw MethodNotAllowedException::forPath($method, $path, $allowed);
        }

        throw RouteNotFoundException::forPath($method, $path);
    }

    public function generate(string $name, array $parameters = []): string
    {
        $route = $this->routes->get($name);

        if ($route === null) {
            throw new RouteNotDefinedException(sprintf('Route "%s" does not exist.', $name));
        }

        return $route->generate($parameters);
    }

    public function add(Route $route): static
    {
        $this->routes->add($route);

        return $this;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }
}