<?php

declare(strict_types=1);

namespace NeoPHP\Package\Yaml;

use NeoPHP\Package\Yaml\Contract\AbstractYaml;
use NeoPHP\Package\Yaml\Parser\Parser;

class YamlManager extends AbstractYaml
{
    public function parse(string $input): mixed
    {
        return (new Parser())->parse($input);
    }
}