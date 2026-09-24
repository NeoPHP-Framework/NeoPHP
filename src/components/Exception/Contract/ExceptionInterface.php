<?php

declare(strict_types=1);

namespace NeoPHP\Component\Exception\Contract;

use Throwable;

interface ExceptionInterface extends Throwable
{
    public function getContext(): array;

    public function setContext(array $context): static;

    public function getStatusCode(): int;

    public function setStatusCode(int $statusCode): static;

    public function getHeaders(): array;

    public function setHeaders(array $headers): static;

    public function getStackTrace(): array;

    public function getPreviousExceptions(): array;

    public function getShortName(): string;

    public function toArray(bool $withTrace = true): array;
}