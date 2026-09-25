<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Event;

use NeoPHP\Component\Event\Contract\AbstractEvent;
use NeoPHP\Package\Orm\Contract\OrmInterface;

abstract class LifecycleEvent extends AbstractEvent
{
    public function __construct(protected object $entity, protected OrmInterface $orm)
    {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getOrm(): OrmInterface
    {
        return $this->orm;
    }
}