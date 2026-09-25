<?php

declare(strict_types=1);

namespace NeoPHP\Component\Database\Helper\Controller;

use NeoPHP\Component\Database\Contract\ConnectionInterface;
use NeoPHP\Component\Database\Contract\DatabaseInterface;

trait DatabaseController
{
    abstract protected function get(string $id): mixed;

    protected function getConnection(?string $name = null): ConnectionInterface
    {
        return $this->get(DatabaseInterface::class)->connection($name);
    }
}