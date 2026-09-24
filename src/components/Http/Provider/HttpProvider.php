<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Http\Contract\HttpInterface;
use NeoPHP\Component\Http\HttpManager;

class HttpProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(HttpInterface::class, HttpManager::class);
        $container->alias(HttpManager::class, HttpInterface::class);
    }
}