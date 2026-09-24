<?php

declare(strict_types=1);

namespace NeoPHP\Package\Yaml\Contract;

use NeoPHP\Package\Yaml\Exception\ParseException;

abstract class AbstractYaml implements YamlInterface
{
    public function parseFile(string $file): mixed
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new ParseException(sprintf('File "%s" does not exist or is not readable', $file));
        }

        try {
            return $this->parse((string) file_get_contents($file));
        } catch (ParseException $exception) {
            throw $exception->withFile($file);
        }
    }
}