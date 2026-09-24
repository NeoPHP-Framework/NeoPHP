<?php

declare(strict_types=1);

namespace NeoPHP\Component\Session;

use NeoPHP\Component\Session\Contract\AbstractSession;

class SessionManager extends AbstractSession
{
    public function __construct(array $options = [], bool $previous = false)
    {
        $this->options = array_replace(static::DEFAULT_OPTIONS, array_intersect_key($options, static::DEFAULT_OPTIONS));
        $this->previous = $previous;
    }
}