<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\Console;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Package\Security\Maker\AuthMaker;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class MakeAuthCommand extends AbstractCommand
{
    protected string $name = 'make:auth';

    protected string $description = 'Generates a login controller and its template. Usage: make:auth [SecurityController] [--twig] [--force]';

    public function __construct(protected ContainerInterface $container)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $root = (string) $this->container->get('kernel.root_path');
        $templates = $this->container->has('kernel.templates_path') ? (string) $this->container->get('kernel.templates_path') : $root . '/templates';
        $maker = new AuthMaker($root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller', 'App\\Controller', $templates);

        try {
            [, $file, $template] = $maker->make($input->getArgument(0, 'SecurityController') ?? 'SecurityController', (bool) $input->getOption('twig', false), (bool) $input->getOption('force', false));
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<success>created</success>  %s', $file));
        $output->writeln(sprintf('<success>created</success>  %s', $template));
        $output->writeln('');
        $output->writeln('Enable the login form in <info>config/packages/security.yaml</info>:');
        $output->writeln('  firewalls:');
        $output->writeln('    main:');
        $output->writeln('      form_login:');
        $output->writeln('        login_path: app_login');
        $output->writeln('        enable_csrf: true');
        $output->writeln('      logout:');
        $output->writeln('        path: app_logout');
        $output->writeln('        target: /');

        return self::SUCCESS;
    }
}