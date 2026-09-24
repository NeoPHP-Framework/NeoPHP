<?php

declare(strict_types=1);

namespace NeoPHP\Component\Flash\Contract;

use NeoPHP\Component\Session\Contract\SessionInterface;

abstract class AbstractFlash implements FlashInterface
{
    protected SessionInterface $session;

    protected string $key = '_flashes';

    public function add(string $type, string $message): static
    {
        $flashes = $this->peekAll();
        $flashes[$type][] = $message;
        $this->session->set($this->key, $flashes);

        return $this;
    }

    public function get(string $type): array
    {
        $flashes = $this->peekAll();
        $messages = $flashes[$type] ?? [];

        if ($messages !== []) {
            unset($flashes[$type]);
            $this->store($flashes);
        }

        return $messages;
    }

    public function peek(string $type): array
    {
        return $this->peekAll()[$type] ?? [];
    }

    public function all(): array
    {
        $flashes = $this->peekAll();

        if ($flashes !== []) {
            $this->store([]);
        }

        return $flashes;
    }

    public function peekAll(): array
    {
        $flashes = $this->session->get($this->key, []);

        return is_array($flashes) ? $flashes : [];
    }

    public function has(string $type): bool
    {
        return $this->peek($type) !== [];
    }

    public function clear(): static
    {
        if ($this->session->has($this->key)) {
            $this->session->remove($this->key);
        }

        return $this;
    }

    protected function store(array $flashes): void
    {
        if ($flashes === []) {
            $this->session->remove($this->key);

            return;
        }

        $this->session->set($this->key, $flashes);
    }
}