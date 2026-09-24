<?php

declare(strict_types=1);

namespace NeoPHP\Component\Middleware;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Middleware\Contract\AbstractMiddlewareManager;

class MiddlewareManager extends AbstractMiddlewareManager
{
    public function __construct(?ContainerInterface $container = null, array $global = [], array $aliases = [], array $groups = [])
    {
        $this->container = $container;

        foreach ($aliases as $name => $class) {
            $this->addAlias((string) $name, (string) $class);
        }

        foreach ($groups as $name => $middlewares) {
            $this->addGroup((string) $name, (array) $middlewares);
        }

        foreach ($global as $middleware) {
            $this->addGlobal((string) $middleware);
        }
    }
}