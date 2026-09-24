<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;

interface KernelInterface
{
    public function boot(): void;

    public function handle(string $method, string $path): string;

    public function run(): void;

    public function getContainer(): ContainerInterface;

    public function getProjectDir(): string;

    public function getConfigDir(): string;

    public function getTemplatesDir(): string;

    public function getEnvironment(): string;

    public function isDebug(): bool;

    public function getParameters(): array;
}