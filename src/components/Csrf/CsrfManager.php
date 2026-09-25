<?php

declare(strict_types=1);

namespace NeoPHP\Component\Csrf;

use NeoPHP\Component\Csrf\Contract\AbstractCsrf;
use NeoPHP\Component\Session\Contract\SessionInterface;

class CsrfManager extends AbstractCsrf
{
    public function __construct(SessionInterface $session, array $options = [])
    {
        $this->session = $session;
        $this->options = array_replace(static::DEFAULT_OPTIONS, array_intersect_key($options, static::DEFAULT_OPTIONS));
    }
}