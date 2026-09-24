<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Exception;

use Throwable;

class AccessDeniedHttpException extends HttpException
{
    public function __construct(string $message = 'Forbidden', array $context = [], ?Throwable $previous = null)
    {
        parent::__construct(403, $message, [], $context, $previous);
    }
}