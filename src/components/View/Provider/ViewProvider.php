<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Routing\Contract\RoutingInterface;
use NeoPHP\Component\View\Contract\ViewInterface;
use NeoPHP\Component\View\ViewManager;

class ViewProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(ViewInterface::class, static function (ContainerInterface $container): ViewInterface {
            $templatesPath = $container->has('kernel.templates_path')
                ? (string) $container->get('kernel.templates_path')
                : getcwd() . DIRECTORY_SEPARATOR . 'templates';

            return new ViewManager([$templatesPath]);
        });

        $container->alias(ViewManager::class, ViewInterface::class);
    }

    public function boot(ContainerInterface $container): void
    {
        $view = $container->get(ViewInterface::class);

        $view->addHelper('asset', static fn (string $path): string => '/' . ltrim($path, '/'));

        if ($container->has(RoutingInterface::class)) {
            $view->addHelper('path', static fn (string $name, array $parameters = []): string => $container->get(RoutingInterface::class)->generate($name, $parameters));
        }

        if ($container->has(ConfigInterface::class)) {
            $view->addHelper('config', static fn (string $key, mixed $default = null): mixed => $container->get(ConfigInterface::class)->get($key, $default));
        }
    }
}