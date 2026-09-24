<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container\Contract;

abstract class AbstractProvider implements ProviderInterface
{
    public const TERMINABLES = 'kernel.terminables';

    public function boot(ContainerInterface $container): void
    {
    }

    protected function registerTerminable(ContainerInterface $container, string $id): void
    {
        $services = $container->bound(self::TERMINABLES) ? (array) $container->get(self::TERMINABLES) : [];

        if (!in_array($id, $services, true)) {
            $services[] = $id;
        }

        $container->instance(self::TERMINABLES, $services);
    }
}