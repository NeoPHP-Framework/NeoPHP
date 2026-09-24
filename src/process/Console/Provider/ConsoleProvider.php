<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Command\InstallCommand;
use NeoPHP\Process\Console\Command\RouteListCommand;
use NeoPHP\Process\Console\Command\ServeCommand;
use NeoPHP\Process\Console\ConsoleManager;
use NeoPHP\Process\Console\Contract\ConsoleInterface;

class ConsoleProvider extends AbstractProvider
{
    public const COMMANDS = [
        InstallCommand::class,
        RouteListCommand::class,
        ServeCommand::class,
    ];

    public function register(ContainerInterface $container): void
    {
        $container->singleton(ConsoleInterface::class, static function (ContainerInterface $container): ConsoleInterface {
            $console = new ConsoleManager($container, $container->has('kernel.version') ? (string) $container->get('kernel.version') : '');

            foreach (self::COMMANDS as $command) {
                $console->add($command);
            }

            return $console;
        });

        $container->alias(ConsoleManager::class, ConsoleInterface::class);
    }
}