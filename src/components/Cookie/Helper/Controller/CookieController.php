<?php

declare(strict_types=1);

namespace NeoPHP\Component\Cookie\Helper\Controller;

use NeoPHP\Component\Cookie\Contract\CookieInterface;

trait CookieController
{
    abstract protected function get(string $id): mixed;

    protected function getCookies(): CookieInterface
    {
        return $this->get(CookieInterface::class);
    }
}