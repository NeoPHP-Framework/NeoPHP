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
use NeoPHP\Process\Console\Discovery\CommandDiscovery;

class ConsoleProvider extends AbstractProvider
{
    public const COMMANDS = [
        InstallCommand::class,
        RouteListCommand::class,
        ServeCommand::class,
    ];

    public const FRAMEWORK_SOURCES = [
        'components' => 'NeoPHP\\Component\\',
        'packages' => 'NeoPHP\\Package\\',
        'process' => 'NeoPHP\\Process\\',
    ];

    public const APPLICATION_NAMESPACE = 'App\\';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(ConsoleInterface::class, static function (ContainerInterface $container): ConsoleInterface {
            $console = new ConsoleManager($container, $container->has('kernel.version') ? (string) $container->get('kernel.version') : '');
            $rootPath = $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd();

            foreach (self::commands($rootPath) as $command) {
                $console->add($command);
            }

            return $console;
        });

        $container->alias(ConsoleManager::class, ConsoleInterface::class);
    }

    protected static function commands(string $rootPath): array
    {
        $discovery = new CommandDiscovery();
        $frameworkPath = dirname(__DIR__, 3);

        foreach (self::FRAMEWORK_SOURCES as $directory => $namespace) {
            $discovery->addSource($frameworkPath . DIRECTORY_SEPARATOR . $directory, $namespace);
        }

        $discovery->addSource($rootPath . DIRECTORY_SEPARATOR . 'src', self::APPLICATION_NAMESPACE);

        return array_values(array_unique([...self::COMMANDS, ...$discovery->discover()]));
    }
}