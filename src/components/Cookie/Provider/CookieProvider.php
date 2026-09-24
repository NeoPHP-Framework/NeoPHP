<?php

declare(strict_types=1);

namespace NeoPHP\Component\Cookie\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Cookie\Contract\CookieInterface;
use NeoPHP\Component\Cookie\CookieManager;
use NeoPHP\Component\Http\Request\Request;

class CookieProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.app.cookie';

    public const SECRET_KEY = 'framework.app.secret';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(CookieInterface::class, static function (ContainerInterface $container): CookieInterface {
            $config = $container->has(ConfigInterface::class) ? $container->get(ConfigInterface::class) : null;
            $request = $container->bound(Request::class) ? $container->get(Request::class) : null;
            $secret = $config?->get(self::SECRET_KEY) ?? ($_SERVER['APP_SECRET'] ?? $_ENV['APP_SECRET'] ?? null);

            return new CookieManager(
                $request !== null ? $request->cookies->all() : $_COOKIE,
                (array) ($config?->get(self::CONFIG_KEY, []) ?? []),
                $secret === null ? null : (string) $secret,
                $request !== null ? $request->isSecure() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            );
        });

        $container->alias(CookieManager::class, CookieInterface::class);
    }
}