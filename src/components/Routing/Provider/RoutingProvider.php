<?php

declare(strict_types=1);

namespace NeoPHP\Component\Routing\Provider;

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
            $routing = new RoutingManager($container->get(YamlInterface::class));
            $configDir = $container->has('kernel.config_dir') ? (string) $container->get('kernel.config_dir') : '';

            foreach (self::ROUTE_FILES as $file) {
                $path = $configDir . DIRECTORY_SEPARATOR . $file;

                if ($configDir !== '' && is_file($path)) {
                    $routing->loadYaml($path);
                    break;
                }
            }

            return $routing;
        });

        $container->alias(RoutingManager::class, RoutingInterface::class);
    }
}