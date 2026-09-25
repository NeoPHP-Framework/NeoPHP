<?php

declare(strict_types=1);

namespace NeoPHP\Package\Security\Helper\Console;

use NeoPHP\Package\Security\Hasher\UserPasswordHasher;
use NeoPHP\Process\Console\Contract\AbstractCommand;
use NeoPHP\Process\Console\IO\Input;
use NeoPHP\Process\Console\IO\Output;
use Throwable;

class SecurityHashPasswordCommand extends AbstractCommand
{
    protected string $name = 'security:hash-password';

    protected string $description = 'Hashes a password with the configured hasher. Usage: security:hash-password <password> [UserClass]';

    public function __construct(protected UserPasswordHasher $hasher)
    {
    }

    public function execute(Input $input, Output $output): int
    {
        $password = $input->getArgument(0);

        if ($password === null || $password === '') {
            $output->writeln('<error>Missing password.</error> Usage: php bin/neo security:hash-password secret [App\Entity\User]');

            return self::INVALID;
        }

        $class = $input->getArgument(1) ?? UserPasswordHasher::DEFAULT_KEY;

        try {
            $hash = $this->hasher->getPasswordHasher($class)->hash($password);
        } catch (Throwable $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return self::FAILURE;
        }

        $output->table(['Key', 'Value'], [
            ['Hasher', $class],
            ['Password hash', $hash],
        ]);

        return self::SUCCESS;
    }
}