<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Template;

class Sections
{
    private array $sections = [];

    public function set(string $name, string $content): void
    {
        $this->sections[$name] = $content;
    }

    public function append(string $name, string $content): void
    {
        $this->sections[$name] = ($this->sections[$name] ?? '') . $content;
    }

    public function has(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public function get(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }
}