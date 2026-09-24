<?php

declare(strict_types=1);

namespace NeoPHP\Component\Flash\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Flash\Contract\FlashInterface;
use NeoPHP\Component\Flash\FlashManager;
use NeoPHP\Component\Session\Contract\SessionInterface;

class FlashProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.app.flash';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(FlashInterface::class, static function (ContainerInterface $container): FlashInterface {
            $config = $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];

            return new FlashManager($container->get(SessionInterface::class), (string) ($config['key'] ?? '_flashes'));
        });

        $container->alias(FlashManager::class, FlashInterface::class);
    }
}