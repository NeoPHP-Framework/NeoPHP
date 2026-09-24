<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Contract;

use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

interface CommandInterface
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    public function getName(): string;

    public function getDescription(): string;

    public function execute(Input $input, Output $output): int;
}