<?php

declare(strict_types=1);

namespace NeoPHP\Component\Service\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Kernel\Cache\ResourceCache;
use NeoPHP\Component\Service\Contract\ServiceInterface;
use NeoPHP\Component\Service\Loader\YamlServiceLoader;
use NeoPHP\Component\Service\ServiceManager;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class ServiceProvider extends AbstractProvider
{
    public const SERVICE_FILES = ['services.yaml', 'services.yml'];

    public const CACHE_DIRECTORY = 'service';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(ServiceInterface::class, static function (ContainerInterface $container): ServiceInterface {
            return new ServiceManager($container, static function (mixed $value) use ($container): mixed {
                return $container->has(ConfigInterface::class) ? $container->get(ConfigInterface::class)->resolve($value) : $value;
            });
        });

        $container->alias(ServiceManager::class, ServiceInterface::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $file = self::servicesFile($container);

        if ($file === null) {
            return;
        }

        $builder = static function () use ($container, $file): array {
            $loader = new YamlServiceLoader($container->get(YamlInterface::class));

            return [$loader->load($file), $loader->getResources()];
        };

        if (!$container->has('kernel.cache_path')) {
            $container->get(ServiceInterface::class)->register($builder()[0]);

            return;
        }

        $environment = $container->has('kernel.environment') ? (string) $container->get('kernel.environment') : 'dev';
        $debug = $container->has('kernel.debug') && (bool) $container->get('kernel.debug');
        $cacheFile = (string) $container->get('kernel.cache_path') . DIRECTORY_SEPARATOR . self::CACHE_DIRECTORY . DIRECTORY_SEPARATOR . 'services.' . $environment . '.php';

        $container->get(ServiceInterface::class)->register((new ResourceCache($cacheFile, $debug))->load($builder));
    }

    protected static function servicesFile(ContainerInterface $container): ?string
    {
        $configPath = $container->has('kernel.config_path') ? (string) $container->get('kernel.config_path') : '';

        foreach (self::SERVICE_FILES as $name) {
            $path = $configPath . DIRECTORY_SEPARATOR . $name;

            if ($configPath !== '' && is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}