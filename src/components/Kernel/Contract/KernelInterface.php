<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;

interface KernelInterface
{
    public function boot(): void;

    public function handle(Request $request): Response;

    public function run(): void;

    public function getContainer(): ContainerInterface;

    public function getRootPath(): string;

    public function getConfigPath(): string;

    public function getPublicPath(): string;

    public function getTemplatesPath(): string;

    public function getEnvironment(): string;

    public function isDebug(): bool;

    public function getParameters(): array;
}