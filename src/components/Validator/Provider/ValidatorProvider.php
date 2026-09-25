<?php

declare(strict_types=1);

namespace NeoPHP\Component\Validator\Provider;

use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Validator\Contract\ValidatorInterface;
use NeoPHP\Component\Validator\ValidatorManager;

class ValidatorProvider extends AbstractProvider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(ValidatorInterface::class, static fn (ContainerInterface $container): ValidatorInterface => new ValidatorManager($container));
        $container->alias(ValidatorManager::class, ValidatorInterface::class);
    }
}