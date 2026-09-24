<?php

declare(strict_types=1);

namespace NeoPHP\Component\Asset\Contract;

use NeoPHP\Component\Asset\Compiler\CompilerInterface;
use NeoPHP\Component\Asset\Manifest\Manifest;

interface AssetInterface
{
    public function url(string $path): string;

    public function compile(string $path): string;

    public function reload(bool $minify = false): array;

    public function clear(): void;

    public function addCompiler(CompilerInterface $compiler): static;

    public function getSourcePath(): string;

    public function getBuildPath(): string;

    public function getManifest(): Manifest;
}