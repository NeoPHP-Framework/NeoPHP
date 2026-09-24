<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Exception;

class MethodNotAllowedException extends RoutingException
{
    protected int $statusCode = 405;

    public static function forPath(string $method, string $path, array $allowedMethods): static
    {
        return new static(
            'No route found for "{method} {path}": method not allowed (allowed: {allowed}).',
            0,
            null,
            ['method' => $method, 'path' => $path, 'allowed' => implode(', ', $allowedMethods), 'allowedMethods' => $allowedMethods],
        );
    }

    public function getAllowedMethods(): array
    {
        return $this->context['allowedMethods'] ?? [];
    }
}