<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Exception;

class RouteNotFoundException extends RoutingException
{
    protected int $statusCode = 404;

    public static function forPath(string $method, string $path): static
    {
        return new static('No route found for "{method} {path}".', 0, null, ['method' => $method, 'path' => $path]);
    }
}