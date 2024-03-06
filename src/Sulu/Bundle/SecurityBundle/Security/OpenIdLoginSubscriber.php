<?php

declare(strict_types=1);

namespace Sulu\Bundle\SecurityBundle\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class OpenIdLoginSubscriber implements EventSubscriberInterface
{
    final public const OPEN_ID_ATTRIBUTES = '_app_open_id_attributes';

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => [
                ['onKernelRequest', 9],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->isMethod('POST')) {
            return;
        }

        $route = $request->attributes->get('_route');
        if ('sulu_admin.login_check' !== $route) {
            return;
        }

        $email = $request->request->get('username');
        $password = $request->request->get('password');

        if (!$email || !\is_string($email)) {
            return;
        }

        // Also return when email and password are set
        if ($password) {
            return;
        }

        // Todo: Implement single sign on.
        //$event->setResponse(new JsonResponse(['method' => 'redirect', 'url' => 'https://www.google.at'], 200));
        $event->setResponse(new JsonResponse(['method' => 'json_login'], 200));
        $event->stopPropagation();
    }
}
