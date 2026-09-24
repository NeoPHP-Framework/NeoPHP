<?php

declare(strict_types=1);

namespace NeoPHP\Component\Flash;

use NeoPHP\Component\Flash\Contract\AbstractFlash;
use NeoPHP\Component\Session\Contract\SessionInterface;

class FlashManager extends AbstractFlash
{
    public function __construct(SessionInterface $session, string $key = '_flashes')
    {
        $this->session = $session;
        $this->key = $key;
    }
}