<?php

declare(strict_types=1);

namespace NeoPHP\Package\Dotenv\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Dotenv\Contract\DotenvInterface;
use NeoPHP\Package\Dotenv\DotenvManager;

class DotenvProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(DotenvInterface::class, DotenvManager::class);
        $container->alias(DotenvManager::class, DotenvInterface::class);
    }
}