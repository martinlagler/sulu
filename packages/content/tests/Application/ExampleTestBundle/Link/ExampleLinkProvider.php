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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Link;

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;

class ExampleLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private readonly ContentManagerInterface $contentManager,
        private readonly ExampleRepository $exampleRepository,
        private readonly ReferenceStoreInterface $exampleReferenceStore,
    ) {
    }

    public function getConfiguration(): LinkConfiguration
    {
        return LinkConfigurationBuilder::create()
            ->setTitle('Example')
            ->setResourceKey(Example::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['id'])
            ->setOverlayTitle('Select Example')
            ->setEmptyText('No example selected')
            ->setIcon('su-document')
            ->getLinkConfiguration();
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT,
        ];

        $examples = $this->exampleRepository->findBy(
            filters: [...$dimensionAttributes, 'ids' => \array_map(function($href) {
                return (int) $href;
            }, $hrefs)],
            selects: [ExampleRepository::GROUP_SELECT_EXAMPLE_WEBSITE => true]
        );

        $result = [];
        foreach ($examples as $example) {
            $dimensionContent = $this->contentManager->resolve($example, $dimensionAttributes);
            $this->exampleReferenceStore->add($example->getId());

            /** @var string|null $url */
            $url = $dimensionContent->getTemplateData()['url'] ?? null;
            if (null === $url) {
                // TODO what to do when there is no url?
                continue;
            }

            $result[] = new LinkItem(
                (string) $example->getId(),
                (string) $dimensionContent->getTitle(),
                $url,
                $published
            );
        }

        return $result;
    }
}
