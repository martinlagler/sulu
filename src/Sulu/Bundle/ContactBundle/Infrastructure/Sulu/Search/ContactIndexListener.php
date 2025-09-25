<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
class ContactIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onAccountChanged(AccountCreatedEvent|AccountModifiedEvent|AccountRemovedEvent $event): void
    {
        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers([
                    AccountInterface::RESOURCE_KEY . '::' . $event->getResourceId(),
                ]),
        );
    }
}
