<?php

declare(strict_types=1);

namespace NeoPHP\Package\Orm\Helper\Controller;

use NeoPHP\Package\Orm\Contract\OrmInterface;
use NeoPHP\Package\Orm\Contract\RepositoryInterface;

trait OrmController
{
    abstract protected function get(string $id): mixed;

    protected function getOrm(): OrmInterface
    {
        return $this->get(OrmInterface::class);
    }

    protected function getRepository(string $entityClass): RepositoryInterface
    {
        return $this->getOrm()->getRepository($entityClass);
    }
}