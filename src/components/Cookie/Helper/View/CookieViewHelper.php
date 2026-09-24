<?php

declare(strict_types=1);

namespace NeoPHP\Component\Cookie\Helper\View;

use NeoPHP\Component\Cookie\Contract\CookieInterface;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;

class CookieViewHelper implements ViewFunctionInterface
{
    public function __construct(protected CookieInterface $cookies)
    {
    }

    public function getName(): string
    {
        return 'cookie';
    }

    public function __invoke(string $name, mixed $default = null, bool $signed = false): mixed
    {
        return $signed ? $this->cookies->getSigned($name, $default) : $this->cookies->get($name, $default);
    }
}