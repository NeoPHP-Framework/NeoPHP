<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Contract;

use NeoPHP\Process\Console\IO\Output;

interface ConsoleInterface
{
    public function add(CommandInterface|string $command): static;

    public function all(): array;

    public function find(string $name): ?CommandInterface;

    public function run(array $argv, ?Output $output = null): int;
}