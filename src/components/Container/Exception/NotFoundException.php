<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Exception;

class NotFoundException extends ContainerException
{
    public static function forId(string $id): static
    {
        return new static('No entry or class found for "{id}".', 0, null, ['id' => $id]);
    }
}