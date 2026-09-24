<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Route;

class RouteMatch
{
    public function __construct(
        public Route $route,
        public array $parameters,
    ) {
    }

    public function getName(): string
    {
        return $this->route->getName();
    }

    public function getController(): mixed
    {
        return $this->route->getController();
    }
}