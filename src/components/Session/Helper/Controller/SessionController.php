<?php

declare(strict_types=1);

namespace NeoPHP\Component\Session\Helper\Controller;

use NeoPHP\Component\Session\Contract\SessionInterface;

trait SessionController
{
    abstract protected function get(string $id): mixed;

    protected function getSession(): SessionInterface
    {
        return $this->get(SessionInterface::class);
    }
}