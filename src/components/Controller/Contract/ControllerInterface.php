<?php

declare(strict_types=1);

namespace NeoPHP\Component\Controller\Contract;

use NeoPHP\Component\Container\Contract\ContainerInterface;

interface ControllerInterface
{
    public function setContainer(ContainerInterface $container): void;
}