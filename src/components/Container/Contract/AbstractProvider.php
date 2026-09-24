<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Contract;

abstract class AbstractProvider implements ProviderInterface
{
    public function boot(ContainerInterface $container): void
    {
    }
}