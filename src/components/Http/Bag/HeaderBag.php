<?php

declare(strict_types=1);

namespace NeoPHP\Component\Http\Bag;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class HeaderBag implements IteratorAggregate, Countable
{
    protected array $headers = [];

    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $values) {
            $this->set((string) $name, $values);
        }
    }

    public static function fromServer(array $server): static
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true) && $value !== '') {
                $headers[str_replace('_', '-', $key)] = (string) $value;
            }
        }

        if (!isset($headers['AUTHORIZATION'])) {
            if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['AUTHORIZATION'] = (string) $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($server['PHP_AUTH_USER'])) {
                $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . ($server['PHP_AUTH_PW'] ?? ''));
            }
        }

        return new static($headers);
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function get(string $name, ?string $default = null): ?string
    {
        $values = $this->headers[$this->normalize($name)] ?? [];

        return $values === [] ? $default : (string) $values[0];
    }

    public function getValues(string $name): array
    {
        return $this->headers[$this->normalize($name)] ?? [];
    }

    public function set(string $name, string|array $values, bool $replace = true): void
    {
        $name = $this->normalize($name);
        $values = array_values(array_map('strval', (array) $values));

        $this->headers[$name] = $replace || !isset($this->headers[$name])
            ? $values
            : [...$this->headers[$name], ...$values];
    }

    public function has(string $name): bool
    {
        return isset($this->headers[$this->normalize($name)]);
    }

    public function remove(string $name): void
    {
        unset($this->headers[$this->normalize($name)]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->headers);
    }

    public function count(): int
    {
        return count($this->headers);
    }

    protected function normalize(string $name): string
    {
        return strtolower(str_replace('_', '-', $name));
    }
}