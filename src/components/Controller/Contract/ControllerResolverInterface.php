<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

interface ControllerResolverInterface
{
    public function resolve(mixed $controller): callable;

    public function resolveArguments(callable $controller, array $routeParameters = []): array;

    public function dispatch(mixed $controller, array $routeParameters = []): string;
}