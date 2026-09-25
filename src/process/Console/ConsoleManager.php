<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Contract\AbstractConsoleManager;

class ConsoleManager extends AbstractConsoleManager
{
    public function __construct(?ContainerInterface $container = null, string $version = '')
    {
        $this->container = $container;
        $this->version = $version;
    }
}