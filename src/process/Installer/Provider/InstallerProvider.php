<?php

declare(strict_types=1);

namespace NeoPHP\Process\Installer\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Installer\Contract\InstallerInterface;
use NeoPHP\Process\Installer\InstallerManager;

class InstallerProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(InstallerInterface::class, InstallerManager::class);
        $container->alias(InstallerManager::class, InstallerInterface::class);
    }
}