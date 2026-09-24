<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Kernel\Contract\KernelInterface;
use NeoPHP\Component\Kernel\KernelManager;

class KernelProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        if ($container->bound(KernelInterface::class) && !$container->bound(KernelManager::class)) {
            $container->alias(KernelManager::class, KernelInterface::class);
        }
    }
}