<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Contract;

use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;

interface TerminableInterface
{
    public const TERMINABLES_ID = 'kernel.terminables';

    public function terminate(Request $request, Response $response): void;
}