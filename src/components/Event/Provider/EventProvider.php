<?php

declare(strict_types=1);

namespace NeoPHP\Component\Event\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Contract\EventDispatcherInterface;
use NeoPHP\Component\Event\Discovery\ListenerDiscovery;
use NeoPHP\Component\Event\EventManager;
use NeoPHP\Component\Kernel\Cache\ResourceCache;

class EventProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.event';

    public const CACHE_DIRECTORY = 'event';

    public const FRAMEWORK_SOURCES = ['components', 'packages', 'process'];

    public function register(ContainerInterface $container): void
    {
        $container->singleton(EventDispatcherInterface::class, static function (ContainerInterface $container): EventDispatcherInterface {
            $dispatcher = new EventManager($container);
            $config = $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];

            foreach ((array) ($config['listeners'] ?? []) as $event => $listeners) {
                foreach ((array) $listeners as $listener) {
                    $listener = is_array($listener) ? $listener : ['listener' => $listener];
                    $dispatcher->addListener((string) $event, [(string) $listener['listener'], (string) ($listener['method'] ?? '__invoke')], (int) ($listener['priority'] ?? 0));
                }
            }

            foreach ((array) ($config['subscribers'] ?? []) as $subscriber) {
                $dispatcher->addSubscriber((string) $subscriber);
            }

            foreach (self::discover($container) as $event => $listeners) {
                foreach ((array) $listeners as [$class, $method, $priority]) {
                    $dispatcher->addListener((string) $event, [(string) $class, (string) $method], (int) $priority);
                }
            }

            return $dispatcher;
        });

        $container->alias(EventManager::class, EventDispatcherInterface::class);
    }

    protected static function discover(ContainerInterface $container): array
    {
        $paths = [];
        $frameworkPath = dirname(__DIR__, 3);

        foreach (self::FRAMEWORK_SOURCES as $directory) {
            $paths[] = $frameworkPath . DIRECTORY_SEPARATOR . $directory;
        }

        if ($container->has('kernel.root_path')) {
            $paths[] = (string) $container->get('kernel.root_path') . DIRECTORY_SEPARATOR . 'src';
        }

        $builder = static function () use ($paths): array {
            $discovery = new ListenerDiscovery($paths);

            return [$discovery->discover(), $discovery->getResources()];
        };

        if (!$container->has('kernel.cache_path')) {
            return $builder()[0];
        }

        $environment = $container->has('kernel.environment') ? (string) $container->get('kernel.environment') : 'dev';
        $debug = $container->has('kernel.debug') && (bool) $container->get('kernel.debug');
        $file = (string) $container->get('kernel.cache_path') . DIRECTORY_SEPARATOR . self::CACHE_DIRECTORY . DIRECTORY_SEPARATOR . 'listeners.' . $environment . '.php';

        return (new ResourceCache($file, $debug))->load($builder);
    }
}