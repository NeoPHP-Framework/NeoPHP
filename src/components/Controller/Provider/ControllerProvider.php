<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Controller\ControllerManager;
use NeoPHP\Component\Controller\Contract\ControllerResolverInterface;

class ControllerProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(ControllerResolverInterface::class, ControllerManager::class);
        $container->alias(ControllerManager::class, ControllerResolverInterface::class);
    }
}