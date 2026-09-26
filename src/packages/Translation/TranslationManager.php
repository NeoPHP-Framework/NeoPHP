<?php

declare(strict_types=1);

namespace NeoPHP\Package\Translation;

use NeoPHP\Package\Translation\Contract\AbstractTranslator;
use NeoPHP\Package\Translation\Exception\TranslationException;
use NeoPHP\Package\Translation\Formatter\MessageFormatter;
use NeoPHP\Package\Translation\Loader\XliffLoader;
use NeoPHP\Package\Translation\Loader\YamlLoader;
use NeoPHP\Package\Yaml\Contract\YamlInterface;

class TranslationManager extends AbstractTranslator
{
    public const DEFAULT_CONFIG = [
        'default_locale' => 'en',
        'locales' => [],
        'fallbacks' => null,
        'path' => 'translations',
        'default_domain' => self::DEFAULT_DOMAIN,
        'format' => 'yaml',
        'detection' => [
            'order' => self::DETECTION_SOURCES,
            'query_parameter' => 'lang',
            'session_key' => '_locale',
            'cookie_name' => 'locale',
            'cookie_lifetime' => 31536000,
        ],
        'cache' => true,
        'extract' => [
            'paths' => ['templates', 'src'],
        ],
    ];

    public function __construct(array $config = [], ?string $rootPath = null, ?string $cachePath = null, bool $debug = false, ?YamlInterface $yaml = null)
    {
        $rootPath = rtrim(str_replace('\\', '/', $rootPath ?? (string) getcwd()), '/');
        $this->config = self::normalizeConfig($config, $rootPath);
        $this->defaultLocale = $this->config['default_locale'];
        $this->locales = $this->config['locales'];
        $this->fallbacks = $this->config['fallbacks'];
        $this->defaultDomain = $this->config['default_domain'];
        $this->path = $this->config['path'];
        $this->cache = $this->config['cache'];
        $this->locale = $this->defaultLocale;
        $this->cachePath = $cachePath !== null ? rtrim(str_replace('\\', '/', $cachePath), '/') : null;
        $this->debug = $debug;
        $this->formatter = new MessageFormatter();
        $this->loaders = ['yaml' => new YamlLoader($yaml), 'xliff' => new XliffLoader()];
    }

    public static function normalizeConfig(array $config, string $rootPath): array
    {
        $config = array_replace(self::DEFAULT_CONFIG, array_filter($config, static fn (mixed $value): bool => $value !== null));
        $config['detection'] = array_replace(self::DEFAULT_CONFIG['detection'], (array) ($config['detection'] ?? []));
        $config['extract'] = array_replace(self::DEFAULT_CONFIG['extract'], (array) ($config['extract'] ?? []));

        $default = self::normalizeLocale((string) $config['default_locale']);

        if ($default === null) {
            throw new TranslationException('The default locale "{locale}" is invalid (packages.translation.default_locale).', 0, null, ['locale' => (string) $config['default_locale']]);
        }

        $locales = [];

        foreach ([$default, ...(array) $config['locales']] as $locale) {
            $normalized = self::normalizeLocale((string) $locale);

            if ($normalized === null) {
                throw new TranslationException('The locale "{locale}" is invalid (packages.translation.locales).', 0, null, ['locale' => (string) $locale]);
            }

            $locales[$normalized] = true;
        }

        $fallbacks = [];

        foreach ((array) ($config['fallbacks'] ?? [$default]) as $locale) {
            $normalized = self::normalizeLocale((string) $locale);

            if ($normalized === null) {
                throw new TranslationException('The fallback locale "{locale}" is invalid (packages.translation.fallbacks).', 0, null, ['locale' => (string) $locale]);
            }

            $fallbacks[$normalized] = true;
        }

        $format = strtolower((string) $config['format']);

        if (!isset(self::FORMATS[$format])) {
            throw new TranslationException('The translation format "{format}" is invalid: use "yaml" or "xliff" (packages.translation.format).', 0, null, ['format' => $format]);
        }

        $order = array_values(array_map(static fn (mixed $source): string => strtolower((string) $source), (array) $config['detection']['order']));
        $unknown = array_diff($order, self::DETECTION_SOURCES);

        if ($unknown !== []) {
            throw new TranslationException('Unknown locale detection source(s) "{sources}": use {allowed} (packages.translation.detection.order).', 0, null, [
                'sources' => implode('", "', $unknown),
                'allowed' => implode(', ', self::DETECTION_SOURCES),
            ]);
        }

        $path = str_replace('\\', '/', (string) $config['path']);

        return [
            'default_locale' => $default,
            'locales' => array_keys($locales),
            'fallbacks' => array_keys($fallbacks),
            'path' => rtrim(self::isAbsolute($path) ? $path : $rootPath . '/' . $path, '/'),
            'default_domain' => (string) $config['default_domain'] !== '' ? (string) $config['default_domain'] : self::DEFAULT_DOMAIN,
            'format' => self::FORMATS[$format],
            'detection' => [
                'order' => array_values(array_unique($order)),
                'query_parameter' => (string) $config['detection']['query_parameter'],
                'session_key' => (string) $config['detection']['session_key'],
                'cookie_name' => (string) $config['detection']['cookie_name'],
                'cookie_lifetime' => (int) $config['detection']['cookie_lifetime'],
            ],
            'cache' => (bool) $config['cache'],
            'extract' => [
                'paths' => array_values(array_map(static function (mixed $directory) use ($rootPath): string {
                    $directory = str_replace('\\', '/', (string) $directory);

                    return rtrim(self::isAbsolute($directory) ? $directory : $rootPath . '/' . $directory, '/');
                }, (array) $config['extract']['paths'])),
            ],
        ];
    }

    protected static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1;
    }
}