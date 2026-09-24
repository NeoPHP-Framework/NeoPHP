<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;

class ContainerProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        if (!$container->bound(ContainerInterface::class)) {
            $container->instance(ContainerInterface::class, $container);
        }
    }
}