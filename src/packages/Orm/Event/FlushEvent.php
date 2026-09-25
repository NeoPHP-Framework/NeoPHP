<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Event;

use NeoPHP\Component\Event\Contract\AbstractEvent;
use NeoPHP\Package\Orm\Contract\OrmInterface;

abstract class FlushEvent extends AbstractEvent
{
    public function __construct(protected OrmInterface $orm)
    {
    }

    public function getOrm(): OrmInterface
    {
        return $this->orm;
    }
}