<?php

declare(strict_types=1);

namespace NeoPHP\Component\Database;

use NeoPHP\Component\Database\Contract\AbstractDatabase;

class DatabaseManager extends AbstractDatabase
{
    public function __construct(array $connections = [], ?string $default = null, string $basePath = '')
    {
        foreach ($connections as $name => $config) {
            $this->addConnection((string) $name, is_array($config) || is_string($config) ? $config : []);
        }

        $this->default = $default ?? (isset($this->configurations[self::DEFAULT_CONNECTION]) || $this->configurations === [] ? self::DEFAULT_CONNECTION : (string) array_key_first($this->configurations));
        $this->basePath = rtrim($basePath, '/\\');
    }
}