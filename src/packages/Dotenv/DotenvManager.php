<?php

declare(strict_types=1);

namespace NeoPHP\Package\Dotenv;

use NeoPHP\Package\Dotenv\Contract\AbstractDotenv;
use NeoPHP\Package\Dotenv\Parser\Parser;

class DotenvManager extends AbstractDotenv
{
    public function parse(string $content, ?string $path = null): array
    {
        $known = [];

        foreach ($_SERVER + $_ENV as $name => $value) {
            if (is_string($name) && is_scalar($value)) {
                $known[$name] = (string) $value;
            }
        }

        return (new Parser())->parse($content, $path, $known);
    }
}