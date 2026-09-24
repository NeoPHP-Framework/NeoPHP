<?php

declare(strict_types=1);

namespace NeoPHP\Package\Yaml\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Yaml\Contract\YamlInterface;
use NeoPHP\Package\Yaml\YamlManager;

class YamlProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(YamlInterface::class, YamlManager::class);
        $container->alias(YamlManager::class, YamlInterface::class);
    }
}