<?php

declare(strict_types=1);

namespace NeoPHP\Component\Csrf\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Csrf\Contract\CsrfInterface;
use NeoPHP\Component\Csrf\CsrfManager;
use NeoPHP\Component\Session\Contract\SessionInterface;

class CsrfProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.csrf';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(CsrfInterface::class, static function (ContainerInterface $container): CsrfInterface {
            $config = $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];

            return new CsrfManager($container->get(SessionInterface::class), $config);
        });

        $container->alias(CsrfManager::class, CsrfInterface::class);
        $container->alias('csrf', CsrfInterface::class);
    }
}