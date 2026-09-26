<?php

declare(strict_types=1);

namespace NeoPHP\Package\Tailwind;

use NeoPHP\Package\Tailwind\Contract\AbstractTailwind;

class TailwindManager extends AbstractTailwind
{
    public function __construct(string $rootPath, array $config = [], ?string $sourcePath = null)
    {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->sourcePath = rtrim(str_replace('\\', '/', $sourcePath ?? $this->rootPath . '/assets'), '/');
        $this->version = self::normalizeVersion((string) ($config['version'] ?? self::DEFAULT_VERSION));
        $this->binary = isset($config['binary']) && is_string($config['binary']) && $config['binary'] !== '' ? $config['binary'] : null;

        if (isset($config['input']) && is_string($config['input']) && $config['input'] !== '') {
            $this->setInput($config['input']);
        }
    }
}