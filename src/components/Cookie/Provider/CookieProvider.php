<?php

declare(strict_types=1);

namespace NeoPHP\Component\Cookie;

use NeoPHP\Component\Cookie\Contract\AbstractCookie;

class CookieManager extends AbstractCookie
{
    public function __construct(array $cookies = [], array $defaults = [], ?string $secret = null, bool $secureRequest = false)
    {
        $this->cookies = $cookies;
        $this->defaults = array_replace(static::DEFAULT_OPTIONS, array_intersect_key($defaults, static::DEFAULT_OPTIONS));
        $this->secret = $secret;
        $this->secureRequest = $secureRequest;
    }
}