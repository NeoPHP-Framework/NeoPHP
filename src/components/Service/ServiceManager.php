<?php

declare(strict_types=1);

namespace NeoPHP\Component\Service;

use Closure;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Service\Contract\AbstractService;

class ServiceManager extends AbstractService
{
    public function __construct(ContainerInterface $container, ?callable $resolver = null)
    {
        $this->container = $container;
        $this->resolver = $resolver === null ? null : Closure::fromCallable($resolver);
    }
}