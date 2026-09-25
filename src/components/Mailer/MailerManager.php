<?php

declare(strict_types=1);

namespace NeoPHP\Component\Mailer;

use NeoPHP\Component\Event\Contract\EventDispatcherInterface;
use NeoPHP\Component\Mailer\Contract\AbstractMailer;
use NeoPHP\Component\Mailer\Contract\TransportInterface;

class MailerManager extends AbstractMailer
{
    public function __construct(TransportInterface $transport, ?EventDispatcherInterface $events = null, array $config = [])
    {
        $this->transport = $transport;
        $this->events = $events;
        $this->config = array_replace_recursive(self::DEFAULT_CONFIG, $config);
    }
}