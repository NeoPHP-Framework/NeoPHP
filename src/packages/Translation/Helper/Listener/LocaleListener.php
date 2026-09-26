<?php

declare(strict_types=1);

namespace NeoPHP\Package\Translation\Helper\Listener;

use NeoPHP\Component\Container\Contract\ContainerInterface;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Kernel\Event\ControllerEvent;
use NeoPHP\Component\Kernel\Event\RequestEvent;
use NeoPHP\Component\Session\Contract\SessionInterface;
use NeoPHP\Package\Translation\Contract\TranslatorInterface;
use NeoPHP\Package\Translation\LocaleDetector;

class LocaleListener
{
    public const SOURCE_ATTRIBUTE = '_locale_source';

    public function __construct(protected ContainerInterface $container)
    {
    }

    #[AsListener(priority: 64)]
    public function onRequest(RequestEvent $event): void
    {
        $this->apply($event->getRequest(), null);
    }

    #[AsListener(priority: 64)]
    public function onController(ControllerEvent $event): void
    {
        $this->apply($event->getRequest(), $event->getParameters());
    }

    protected function apply(Request $request, ?array $routeParameters): void
    {
        if (!$this->container->has(TranslatorInterface::class)) {
            return;
        }

        $translator = $this->container->get(TranslatorInterface::class);

        if (count($translator->getLocales()) < 2 && !isset($routeParameters[TranslatorInterface::ATTRIBUTE])) {
            $request->attributes->set(TranslatorInterface::ATTRIBUTE, $translator->getLocale());

            return;
        }

        $session = $this->container->has(SessionInterface::class) ? $this->container->get(SessionInterface::class) : null;
        $detected = $this->container->get(LocaleDetector::class)->detect($request, $routeParameters, $session);
        [$locale, $source] = $detected ?? [$translator->getDefaultLocale(), 'default'];

        $translator->setLocale($locale);
        $request->attributes->set(TranslatorInterface::ATTRIBUTE, $translator->getLocale());
        $request->attributes->set(self::SOURCE_ATTRIBUTE, $source);
    }
}