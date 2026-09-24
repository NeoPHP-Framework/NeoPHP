<?php

declare(strict_types=1);

namespace NeoPHP\Component\Kernel\Helper\Console;

use FilesystemIterator;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class CacheClearCommand extends AbstractCommand
{
    protected string $name = 'cache:clear';

    protected string $description = 'Clears the application cache (var/cache/): routes, Twig templates...';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $cachePath = (string) $this->container->get('kernel.cache_path');

        if (!is_dir($cachePath)) {
            $output->writeln(sprintf('<comment>The cache directory %s does not exist.</comment>', $cachePath));

            return self::SUCCESS;
        }

        $removed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cachePath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || $file->getFilename() === '.gitkeep') {
                continue;
            }

            if ($file->isDir()) {
                @rmdir($file->getPathname());
                continue;
            }

            if (@unlink($file->getPathname())) {
                $removed++;
            }
        }

        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $output->writeln(sprintf('<success>Cache cleared.</success> %d file(s) removed from %s', $removed, $cachePath));

        return self::SUCCESS;
    }
}