<?php

declare(strict_types=1);

namespace NeoPHP\Component\Container;

use NeoPHP\Component\Container\Contract\AbstractContainer;
use NeoPHP\Component\Container\Contract\ContainerInterface;

class ContainerManager extends AbstractContainer
{
    public function __construct()
    {
        $this->instance(static::class, $this);

        foreach ([self::class, ContainerInterface::class] as $id) {
            if ($id !== static::class) {
                $this->alias($id, static::class);
            }
        }
    }
}