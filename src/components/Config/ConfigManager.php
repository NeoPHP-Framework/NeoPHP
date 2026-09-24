<?php

declare(strict_types=1);

namespace NeoPHP\Component\Config;

use NeoPHP\Component\Config\Contract\AbstractConfig;
use NeoPHP\Component\Config\Exception\ConfigException;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class ConfigManager extends AbstractConfig
{
    public function __construct(
        protected YamlInterface $yaml,
        array $parameters = [],
    ) {
        foreach ($parameters as $key => $value) {
            $this->set((string) $key, $value);
        }
    }

    public function loadDirectory(string $directory, array $exclude = []): static
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        if (!is_dir($directory)) {
            return $this;
        }

        $exclude = array_map(static fn (string $path): string => trim(str_replace('\\', '/', $path), '/'), $exclude);
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['yaml', 'yml'], true)) {
                continue;
            }

            $relative = ltrim(substr(str_replace('\\', '/', $file->getPathname()), strlen($directory)), '/');

            foreach ($exclude as $excluded) {
                if ($relative === $excluded || str_starts_with($relative, $excluded . '/')) {
                    continue 2;
                }
            }

            $files[$relative] = $file->getPathname();
        }

        ksort($files);

        foreach ($files as $relative => $path) {
            $key = str_replace('/', '.', (string) preg_replace('/\.ya?ml$/i', '', $relative));
            $this->loadFile($path, $key, false);
        }

        $this->items = $this->resolve($this->items);

        return $this;
    }

    public function loadFile(string $file, string $key = '', bool $resolve = true): static
    {
        $data = $this->yaml->parseFile($file) ?? [];

        if (!is_array($data)) {
            throw new ConfigException('The configuration file "{file}" must contain a mapping.', 0, null, ['file' => $file]);
        }

        if ($resolve) {
            $data = $this->resolve($data);
        }

        if ($key === '') {
            $this->items = $this->merge($this->items, $data);

            return $this;
        }

        $existing = $this->get($key);
        $this->set($key, is_array($existing) ? $this->merge($existing, $data) : $data);

        return $this;
    }
}