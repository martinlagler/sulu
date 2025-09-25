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

namespace Sulu\Bundle\ContactBundle\Tests\Unit\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRemovedEvent;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\ContactIndexListener;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(ContactIndexListenerTest::class)]
class ContactIndexListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private ContactIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new ContactIndexListener($this->messageBus->reveal());
    }

    public function testOnAccountChangedWithAccountCreatedEvent(): void
    {
        $account = new Account();
        $account->setId(123);
        $event = new AccountCreatedEvent($account, []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([AccountInterface::RESOURCE_KEY . '::123']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onAccountChanged($event);
    }

    public function testOnAccountChangedWithAccountModifiedEvent(): void
    {
        $account = new Account();
        $account->setId(456);
        $event = new AccountModifiedEvent($account, []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([AccountInterface::RESOURCE_KEY . '::456']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onAccountChanged($event);
    }

    public function testOnAccountChangedWithAccountRemovedEvent(): void
    {
        $account = new Account();
        $account->setId(789);
        $account->setName('Sulu GmbH');
        $event = new AccountRemovedEvent($account->getId(), $account->getName());

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([AccountInterface::RESOURCE_KEY . '::789']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onAccountChanged($event);
    }
}
