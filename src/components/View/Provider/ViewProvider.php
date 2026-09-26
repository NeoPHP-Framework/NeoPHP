<?php

declare(strict_types=1);

namespace NeoPHP\Component\View\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\View\Contract\ViewHelperInterface;
use NeoPHP\Component\View\Contract\ViewInterface;
use NeoPHP\Component\View\Discovery\HelperDiscovery;
use NeoPHP\Component\View\Engine\PhpEngine;
use NeoPHP\Component\View\Engine\TwigEngine;
use NeoPHP\Component\View\Exception\ViewException;
use NeoPHP\Component\View\ViewManager;

class ViewProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.view';

    public const FRAMEWORK_SOURCES = [
        'components' => 'NeoPHP\\Component\\',
        'packages' => 'NeoPHP\\Package\\',
        'process' => 'NeoPHP\\Process\\',
    ];

    public const APPLICATION_NAMESPACE = 'App\\';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(ViewInterface::class, static function (ContainerInterface $container): ViewInterface {
            $config = self::config($container);
            $rootPath = $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd();
            $templatesPath = $container->has('kernel.templates_path') ? (string) $container->get('kernel.templates_path') : $rootPath . DIRECTORY_SEPARATOR . 'templates';
            $debug = $container->has('kernel.debug') && (bool) $container->get('kernel.debug');

            $engines = [new PhpEngine()];

            if (TwigEngine::isAvailable() && ($config['twig']['enabled'] ?? true) !== false) {
                $engines[] = new TwigEngine(array_replace([
                    'cache' => $rootPath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'twig',
                    'debug' => $debug,
                    'auto_reload' => true,
                    'strict_variables' => $debug,
                ], (array) ($config['twig'] ?? [])));
            }

            $view = new ViewManager((array) ($config['paths'] ?? [$templatesPath]), $engines);

            foreach ((array) ($config['namespaces'] ?? []) as $namespace => $path) {
                $view->addPath((string) $path, (string) $namespace);
            }

            foreach (self::helpers($rootPath, $config, $debug) as $class) {
                $helper = $container->get($class);

                if (!$helper instanceof ViewHelperInterface) {
                    throw new ViewException('The view helper "{class}" must implement {interface}.', 0, null, [
                        'class' => $class,
                        'interface' => ViewHelperInterface::class,
                    ]);
                }

                $view->addExtension($helper);
            }

            return $view;
        });

        $container->alias(ViewManager::class, ViewInterface::class);
    }

    protected static function helpers(string $rootPath, array $config, bool $debug = false): array
    {
        $discovery = new HelperDiscovery([], $debug);
        $frameworkPath = dirname(__DIR__, 3);

        foreach (self::FRAMEWORK_SOURCES as $directory => $namespace) {
            $discovery->addSource($frameworkPath . DIRECTORY_SEPARATOR . $directory, $namespace);
        }

        $discovery->addSource($rootPath . DIRECTORY_SEPARATOR . 'src', self::APPLICATION_NAMESPACE);

        $helpers = $discovery->discover();

        foreach ((array) ($config['helpers'] ?? []) as $helper) {
            $helpers[] = (string) $helper;
        }

        return array_values(array_unique($helpers));
    }

    protected static function config(ContainerInterface $container): array
    {
        if (!$container->has(ConfigInterface::class)) {
            return [];
        }

        return (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []);
    }
}