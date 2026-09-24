<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\Routing\RoutingManager;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class RoutingProvider extends AbstractProvider
{
    public const ROUTE_FILES = ['routes.yaml', 'routes.yml'];

    public function register(ContainerInterface $container): void
    {
        $container->singleton(RoutingInterface::class, static function (ContainerInterface $container): RoutingInterface {
            $resolver = null;

            if ($container->has(ConfigInterface::class)) {
                $config = $container->get(ConfigInterface::class);
                $resolver = static fn (array $definitions): array => $config->resolve($definitions);
            }

            $routing = new RoutingManager($container->get(YamlInterface::class), null, $resolver);
            $configPath = $container->has('kernel.config_path') ? (string) $container->get('kernel.config_path') : '';

            foreach (self::ROUTE_FILES as $file) {
                $path = $configPath . DIRECTORY_SEPARATOR . $file;

                if ($configPath !== '' && is_file($path)) {
                    $routing->loadYaml($path);
                    break;
                }
            }

            return $routing;
        });

        $container->alias(RoutingManager::class, RoutingInterface::class);
    }
}