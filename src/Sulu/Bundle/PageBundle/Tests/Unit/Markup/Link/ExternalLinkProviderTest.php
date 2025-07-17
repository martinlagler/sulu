<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Markup\Link;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\PageBundle\Markup\Link\ExternalLinkProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExternalLinkProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var string
     */
    protected $locale = 'en';

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    protected $translator;

    /**
     * @var ExternalLinkProvider
     */
    protected $externalLinkProvider;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->externalLinkProvider = new ExternalLinkProvider(
            $this->translator->reveal(),
        );
    }

    public function testGetConfiguration(): void
    {
        $this->translator->trans('sulu_admin.external_link', [], 'admin')->willReturn('External Link');

        /** @var LinkConfigurationBuilder $externalLinkProviderConfiguration */
        $externalLinkProviderConfiguration = $this->externalLinkProvider->getConfiguration();
        $this->assertEquals(
            new LinkConfiguration(
                'External Link',
                '',
                '',
                [],
                '',
                '',
                '',
                [
                    '_blank',
                    '_self',
                    '_parent',
                    '_top',
                ],
            ),
            $externalLinkProviderConfiguration->getLinkConfiguration()
        );
    }

    public function testPreload(): void
    {
        $url1 = 'https://sulu.io/';
        $url2 = 'https://sulu.rocks/en';

        /** @var LinkItem[] $result */
        $result = $this->externalLinkProvider->preload([$url1, $url2], $this->locale);

        $this->assertCount(2, $result);

        $this->assertEquals($url1, $result[0]->getUrl());
        $this->assertEquals($url1, $result[0]->getTitle());
        $this->assertTrue($result[0]->isPublished());

        $this->assertEquals($url2, $result[1]->getUrl());
        $this->assertEquals($url2, $result[1]->getTitle());
        $this->assertTrue($result[1]->isPublished());
    }
}
