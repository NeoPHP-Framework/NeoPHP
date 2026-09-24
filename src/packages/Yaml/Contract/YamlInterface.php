<?php

declare(strict_types=1);

namespace NeoPHP\Package\Yaml\Contract;

interface YamlInterface
{
    public function parse(string $input): mixed;

    public function parseFile(string $file): mixed;
}