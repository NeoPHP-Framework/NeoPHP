<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Exception;

use Throwable;

class BadRequestHttpException extends HttpException
{
    public function __construct(string $message = 'Bad Request', array $context = [], ?Throwable $previous = null)
    {
        parent::__construct(400, $message, [], $context, $previous);
    }
}