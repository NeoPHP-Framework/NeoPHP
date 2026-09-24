<?php

declare(strict_types=1);

namespace NeoPHP\Component\Exception\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Exception\ExceptionManager;

class ExceptionProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(ExceptionManager::class, static function (ContainerInterface $container): ExceptionManager {
            return new ExceptionManager($container->has('kernel.debug') && (bool) $container->get('kernel.debug'));
        });
    }
}