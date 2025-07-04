<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
	private string $defaultLocale;

	public function __construct(string $defaultLocale = 'de')
	{
		$this->defaultLocale = $defaultLocale;
	}

	public function onKernelRequest(RequestEvent $event): void
	{
		$request = $event->getRequest();

		if ($locale = $request->getSession()->get('_locale')) {
			$request->setLocale($locale);
		} else {
			$request->setLocale($this->defaultLocale);
		}
	}

	public static function getSubscribedEvents(): array
	{
		return [
			KernelEvents::REQUEST => [['onKernelRequest', 20]],
		];
	}
}
