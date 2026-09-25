<?php

declare(strict_types=1);

namespace NeoPHP\Component\Form\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Csrf\Contract\CsrfInterface;
use NeoPHP\Component\Form\Contract\FormManagerInterface;
use NeoPHP\Component\Form\FormManager;
use NeoPHP\Component\Form\Renderer\FormRenderer;
use NeoPHP\Component\Validator\Contract\ValidatorInterface;

class FormProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.form';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(FormManagerInterface::class, static function (ContainerInterface $container): FormManagerInterface {
            $config = $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];

            return new FormManager(
                $container,
                $container->has(ValidatorInterface::class) ? $container->get(ValidatorInterface::class) : null,
                $container->has(CsrfInterface::class) ? $container->get(CsrfInterface::class) : null,
                $config,
            );
        });

        $container->singleton(FormRenderer::class, static fn (ContainerInterface $container): FormRenderer => $container->get(FormManagerInterface::class)->getRenderer());
        $container->alias(FormManager::class, FormManagerInterface::class);
        $container->alias('form', FormManagerInterface::class);
    }
}