<?php

declare(strict_types=1);

namespace NeoPHP\Component\Logger\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Logger\Contract\LoggerInterface;
use NeoPHP\Component\Logger\Contract\LoggerManagerInterface;
use NeoPHP\Component\Logger\LoggerManager;

class LoggerProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.logger';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(LoggerManagerInterface::class, static function (ContainerInterface $container): LoggerManagerInterface {
            $config = $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];
            $rootPath = $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd();

            return LoggerManager::fromConfig($config, $rootPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log');
        });

        $container->alias(LoggerManager::class, LoggerManagerInterface::class);
        $container->alias(LoggerInterface::class, LoggerManagerInterface::class);
    }
}