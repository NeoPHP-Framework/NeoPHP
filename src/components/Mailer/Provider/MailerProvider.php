<?php

declare(strict_types=1);

namespace NeoPHP\Component\Mailer\Provider;

use NeoPHP\Component\Config\Contract\ConfigInterface;
use NeoPHP\Component\Container\Contract\AbstractProvider;
use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Contract\EventDispatcherInterface;
use NeoPHP\Component\Logger\Contract\LoggerInterface;
use NeoPHP\Component\Mailer\Contract\MailerInterface;
use NeoPHP\Component\Mailer\Contract\TransportInterface;
use NeoPHP\Component\Mailer\MailerManager;
use NeoPHP\Component\Mailer\Transport\TransportFactory;

class MailerProvider extends AbstractProvider
{
    public const CONFIG_KEY = 'framework.mailer';

    public function register(ContainerInterface $container): void
    {
        $container->singleton(TransportFactory::class, static fn (ContainerInterface $container): TransportFactory => new TransportFactory(
            $container->has('kernel.root_path') ? (string) $container->get('kernel.root_path') : (string) getcwd(),
            $container->has(LoggerInterface::class) ? $container->get(LoggerInterface::class) : null,
        ));

        $container->singleton(TransportInterface::class, static fn (ContainerInterface $container): TransportInterface => $container->get(TransportFactory::class)->create((string) (self::config($container)['dsn'] ?? 'null://null')));

        $container->singleton(MailerInterface::class, static fn (ContainerInterface $container): MailerInterface => new MailerManager(
            $container->get(TransportInterface::class),
            $container->has(EventDispatcherInterface::class) ? $container->get(EventDispatcherInterface::class) : null,
            self::config($container),
        ));

        $container->alias(MailerManager::class, MailerInterface::class);
        $container->alias('mailer', MailerInterface::class);
        $container->alias('mailer.transport', TransportInterface::class);
    }

    public static function config(ContainerInterface $container): array
    {
        return $container->has(ConfigInterface::class) ? (array) $container->get(ConfigInterface::class)->get(self::CONFIG_KEY, []) : [];
    }
}