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

namespace Sulu\Bundle\ContactBundle\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search\AccountReindexProvider;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AccountReindexProviderTest extends SuluTestCase
{
    private EntityManagerInterface $entityManager;
    private AccountReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new AccountReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', AccountReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->assertNull($this->provider->total());
    }

    public function testProvideAll(): void
    {
        $account1 = $this->createAccount('Test Account 1');
        $account2 = $this->createAccount('Test Account 2');

        $this->entityManager->flush();

        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-06-01 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE co_accounts SET changed = :changed WHERE id = :id';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'id' => $account1->getId(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'id' => $account2->getId(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $this->assertSame(
            [
                [
                    'id' => AccountInterface::RESOURCE_KEY . '::' . $account1->getId(),
                    'resourceKey' => AccountInterface::RESOURCE_KEY,
                    'resourceId' => (string) $account1->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $account1->getName(),
                ],
                [
                    'id' => AccountInterface::RESOURCE_KEY . '::' . $account2->getId(),
                    'resourceKey' => AccountInterface::RESOURCE_KEY,
                    'resourceId' => (string) $account2->getId(),
                    'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                    'createdAt' => (new \DateTimeImmutable('2000-01-01 12:00:00'))->format('c'),
                    'title' => $account2->getName(),
                ],
            ],
            [...$results],
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $account1 = $this->createAccount('Account One');
        $account2 = $this->createAccount('Account Two');
        $account3 = $this->createAccount('Account Three');

        $this->entityManager->flush();

        $identifiers = [
            AccountInterface::RESOURCE_KEY . '::' . $account1->getId(),
            AccountInterface::RESOURCE_KEY . '::' . $account3->getId(),
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Account One', $resultTitles);
        $this->assertContains('Account Three', $resultTitles);
        $this->assertNotContains('Account Two', $resultTitles);
    }

    private function createAccount(string $name): Account
    {
        $account = new Account();
        $account->setName($name);
        $account->setCreated(new \DateTimeImmutable('2000-01-01 12:00:00'));

        $this->entityManager->persist($account);

        return $account;
    }
}
