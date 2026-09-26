<?php

declare(strict_types=1);

namespace NeoPHP\Package\Tailwind\Provider;

use NeoPHP\Component\Asset\Contract\AssetInterface;
use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Tailwind\Contract\TailwindInterface;
use NeoPHP\Package\Tailwind\TailwindManager;

class TailwindProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'packages.tailwind';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(TailwindInterface::class, static function (ContainerInterface $container): TailwindInterface {
            $rootPath = $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd();
            $sourcePath = $container->has(AssetInterface::class) ? $container->get(AssetInterface::class)->getSourcePath() : null;

            return new TailwindManager($rootPath, self::config($container), $sourcePath);
        });

        $container->alias(TailwindManager::class, TailwindInterface::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $input = self::config($container)['input'] ?? null;

        if (!is_string($input) || $input === '' || !$container->has(AssetInterface::class)) {
            return;
        }

        $tailwind = $container->get(TailwindInterface::class);
        $container->get(AssetInterface::class)->setSourceFile((string) $tailwind->getInput(), $tailwind->getOutputFile((string) $tailwind->getInput()));
    }

    protected static function config(ContainerInterface $container): array
    {
        return $container->has(ConfigInterface::class) ? (array) ($container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) ?? []) : [];
    }
}