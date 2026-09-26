<?php

declare(strict_types=1);

namespace NeoPHP\Package\Markdown\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\View\Contract\ViewInterface;
use NeoPHP\Package\Markdown\Contract\MarkdownParserInterface;
use NeoPHP\Package\Markdown\MarkdownManager;

class MarkdownProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(MarkdownParserInterface::class, static function (ContainerInterface $container): MarkdownParserInterface {
            $rootPath = $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd();
            $templatesPath = $container->has('kernel.templates_path') ? (string) $container->get('kernel.templates_path') : null;

            return new MarkdownManager($rootPath, $templatesPath, static fn (): ?ViewInterface => $container->has(ViewInterface::class) ? $container->get(ViewInterface::class) : null);
        });

        $container->alias(MarkdownManager::class, MarkdownParserInterface::class);
    }
}