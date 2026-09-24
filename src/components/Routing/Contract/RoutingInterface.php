<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Contract;

use NeoPHP\Component\Routing\Route\Route;
use NeoPHP\Component\Routing\Route\RouteCollection;
use NeoPHP\Component\Routing\Route\RouteMatch;

interface RoutingInterface
{
    public function match(string $method, string $path): RouteMatch;

    public function generate(string $name, array $parameters = []): string;

    public function add(Route $route): static;

    public function loadYaml(string $file): static;

    public function getRoutes(): RouteCollection;
}