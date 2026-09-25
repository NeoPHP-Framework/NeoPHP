<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Exception;

class AuthenticationException extends SecurityException
{
    protected int $statusCode = 401;

    protected string $safeMessage = 'An authentication exception occurred.';

    public function getSafeMessage(): string
    {
        return static::interpolate($this->safeMessage, $this->context);
    }
}