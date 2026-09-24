<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Exception;

use Throwable;

class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Not Found', array $context = [], ?Throwable $previous = null)
    {
        parent::__construct(404, $message, [], $context, $previous);
    }
}