<?php

declare(strict_types=1);

namespace NeoPHP\Component\Mailer\Contract;

use NeoPHP\Component\Mailer\Message\Envelope;
use NeoPHP\Component\Mailer\Message\SentMessage;
use NeoPHP\Component\Mailer\Mime\Email;

interface MailerInterface
{
    public function send(Email $email, ?Envelope $envelope = null): ?SentMessage;

    public function getTransport(): TransportInterface;

    public function getConfig(): array;
}