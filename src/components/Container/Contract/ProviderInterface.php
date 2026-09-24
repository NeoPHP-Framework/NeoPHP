<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Contract;

interface ProviderInterface
{
    public function register(ContainerInterface $container): void;

    public function boot(ContainerInterface $container): void;
}