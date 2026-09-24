<?php

declare(strict_types=1);

namespace NeoPHP\Component\Asset;

use NeoPHP\Component\Asset\Compiler\CssCompiler;
use NeoPHP\Component\Asset\Compiler\JsCompiler;
use NeoPHP\Component\Asset\Contract\AbstractAsset;
use NeoPHP\Component\Asset\Exception\AssetException;

class AssetManager extends AbstractAsset
{
    public function __construct(
        string $sourcePath,
        string $buildPath,
        string $publicUrl = '/builds',
        bool $autoCompile = false,
        string $hashAlgorithm = 'xxh128',
        int $hashLength = 8,
        ?array $compilers = null,
    ) {
        if (!in_array($hashAlgorithm, hash_algos(), true)) {
            throw new AssetException('The hash algorithm "{algorithm}" is not supported.', 0, null, ['algorithm' => $hashAlgorithm]);
        }

        $this->sourcePath = rtrim(str_replace('\\', '/', $sourcePath), '/');
        $this->buildPath = rtrim(str_replace('\\', '/', $buildPath), '/');
        $this->publicUrl = rtrim($publicUrl, '/');
        $this->autoCompile = $autoCompile;
        $this->hashAlgorithm = $hashAlgorithm;
        $this->hashLength = max(4, $hashLength);

        foreach ($compilers ?? [new CssCompiler(), new JsCompiler()] as $compiler) {
            $this->addCompiler($compiler);
        }
    }

    public static function fromConfig(array $config, array $defaults = []): static
    {
        $config = array_replace($defaults, $config);
        $hash = (array) ($config['hash'] ?? []);

        return new static(
            (string) ($config['source_path'] ?? 'assets'),
            (string) ($config['build_path'] ?? 'public/builds'),
            (string) ($config['public_url'] ?? '/builds'),
            (bool) ($config['auto_compile'] ?? false),
            (string) ($hash['algorithm'] ?? 'xxh128'),
            (int) ($hash['length'] ?? 8),
        );
    }
}