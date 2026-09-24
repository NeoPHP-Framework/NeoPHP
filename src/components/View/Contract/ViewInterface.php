<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Contract;

interface ViewInterface
{
    public function render(string $template, array $parameters = []): string;

    public function exists(string $template): bool;

    public function addPath(string $path, ?string $namespace = null): static;

    public function addGlobal(string $name, mixed $value): static;

    public function addHelper(string $name, callable $helper): static;
}