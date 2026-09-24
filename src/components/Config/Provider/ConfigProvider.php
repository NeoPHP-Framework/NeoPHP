<?php

declare(strict_types=1);

namespace NeoPHP\Component\Config\Provider;

use NeoPHP\Component\Config\ConfigManager;
use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class ConfigProvider extends AbstractProvider
{
    public const PARAMETERS_ID = 'kernel.parameters';

    public const EXCLUDED = ['routes.yaml', 'routes.yml', 'routes'];

    public function register(ContainerInterface $container): void
    {
        $container->singleton(ConfigInterface::class, static function (ContainerInterface $container): ConfigInterface {
            $parameters = $container->has(self::PARAMETERS_ID) ? (array) $container->get(self::PARAMETERS_ID) : [];
            $config = new ConfigManager($container->get(YamlInterface::class), $parameters);
            $configPath = $config->get('kernel.config_path');

            if (is_string($configPath)) {
                $config->loadDirectory($configPath, self::EXCLUDED);
            }

            return $config;
        });

        $container->alias(ConfigManager::class, ConfigInterface::class);
    }
}