<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\IO;

class Input
{
    public function __construct(protected array $arguments = [], protected array $options = [])
    {
    }

    public static function fromTokens(array $tokens): static
    {
        $arguments = [];
        $options = [];
        $onlyArguments = false;

        foreach ($tokens as $token) {
            $token = (string) $token;

            if ($onlyArguments) {
                $arguments[] = $token;
            } elseif ($token === '--') {
                $onlyArguments = true;
            } elseif (str_starts_with($token, '--')) {
                $parts = explode('=', substr($token, 2), 2);
                $options[$parts[0]] = $parts[1] ?? true;
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                foreach (str_split(substr($token, 1)) as $flag) {
                    $options[$flag] = true;
                }
            } else {
                $arguments[] = $token;
            }
        }

        return new static($arguments, $options);
    }

    public function getArgument(int $index, ?string $default = null): ?string
    {
        return $this->arguments[$index] ?? $default;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }
}