<?php

declare(strict_types=1);

namespace NeoPHP\Process\Console\Command;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;

class ServeCommand extends AbstractCommand
{
    protected string $name = 'serve';

    protected string $description = 'Starts the PHP development server (--host=127.0.0.1 --port=8000)';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $host = (string) $input->getOption('host', '127.0.0.1');
        $port = (string) $input->getOption('port', '8000');
        $publicDir = (string) $this->container->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public';

        if (!is_file($publicDir . DIRECTORY_SEPARATOR . 'index.php')) {
            $output->writeln(sprintf('<error>No front controller found in "%s". Run "php vendor/bin/neo install" first.</error>', $publicDir));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>NeoPHP server running on http://%s:%s</success> <muted>(Ctrl+C to stop)</muted>', $host, $port));

        passthru(sprintf(
            '%s -S %s -t %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host . ':' . $port),
            escapeshellarg($publicDir),
            escapeshellarg(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'router.php'),
        ), $exitCode);

        return (int) $exitCode;
    }
}