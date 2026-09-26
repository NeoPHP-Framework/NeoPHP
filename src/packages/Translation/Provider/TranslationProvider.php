<?php

declare(strict_types=1);

namespace NeoPHP\Package\Translation\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;
use NeoPHP\Package\Translation\LocaleDetector;
use NeoPHP\Package\Translation\TranslationManager;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class TranslationProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'packages.translation';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(TranslatorInterface::class, static function (ContainerInterface $container): TranslatorInterface {
            $config = $container->has(ConfigInterface::class) ? (array) ($container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) ?? []) : [];

            return new TranslationManager(
                $config,
                $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : null,
                $container->has('kernel.cache_path') ? (string) $container->get('kernel.cache_path') : null,
                $container->has('kernel.debug') && (bool) $container->get('kernel.debug'),
                $container->has(YamlInterface::class) ? $container->get(YamlInterface::class) : null,
            );
        });

        $container->singleton(LocaleDetector::class, static fn (ContainerInterface $container): LocaleDetector => new LocaleDetector($container->get(TranslatorInterface::class)));
        $container->alias(TranslationManager::class, TranslatorInterface::class);
        $container->alias('translator', TranslatorInterface::class);
    }
}