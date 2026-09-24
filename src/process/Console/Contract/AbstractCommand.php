<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Contract;

use NeoPHP\Process\Console\Exception\ConsoleException;

abstract class AbstractCommand implements CommandInterface
{
    protected string $name = '';

    protected string $description = '';

    public function getName(): string
    {
        if ($this->name === '') {
            throw new ConsoleException('The command "{command}" must define a name.', 0, null, ['command' => static::class]);
        }

        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}