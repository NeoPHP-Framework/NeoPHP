<?php

declare(strict_types=1);

namespace NeoPHP\Package\Debug;

use NeoPHP\Package\Debug\Cloner\VarCloner;
use NeoPHP\Package\Debug\Contract\AbstractDebug;
use NeoPHP\Package\Debug\Dumper\HtmlDumper;

class DebugManager extends AbstractDebug
{
    public function __construct(array $options = [])
    {
        $this->options = array_replace(static::DEFAULT_OPTIONS, array_intersect_key($options, static::DEFAULT_OPTIONS));
        $this->cloner = new VarCloner((int) $this->options['max_depth'], (int) $this->options['max_items'], (int) $this->options['max_string']);
        $this->htmlDumper = new HtmlDumper((int) $this->options['expand_depth']);
    }
}