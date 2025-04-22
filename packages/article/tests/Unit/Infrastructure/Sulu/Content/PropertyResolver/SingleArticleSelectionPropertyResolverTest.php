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

namespace Sulu\Bundle\ArticleBundle\Tests\Unit\Infrastructure\Sulu\Content\PropertyResolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\SingleArticleSelectionPropertyResolver;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

#[CoversClass(SingleArticleSelectionPropertyResolver::class)]
class SingleArticleSelectionPropertyResolverTest extends TestCase
{
    private SingleArticleSelectionPropertyResolver $resolver;

    public function setUp(): void
    {
        $this->resolver = new SingleArticleSelectionPropertyResolver();
    }

    public function testResolveNull(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
    }

    public function testResolveParams(): void
    {
        $contentView = $this->resolver->resolve(null, 'en', ['custom' => 'params']);

        $this->assertNull($contentView->getContent());
        $this->assertSame([
            'id' => null,
            'custom' => 'params',
        ], $contentView->getView());
    }

    #[DataProvider('provideUnresolvableData')]
    public function testResolveUnresolvableData(mixed $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $this->assertNull($contentView->getContent());
        $this->assertSame(['id' => null], $contentView->getView());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function provideUnresolvableData(): iterable
    {
        yield 'null' => [null];
        yield 'smart_content' => [['source' => '123']];
        yield 'single_int_value' => [1];
        yield 'object' => [(object) [1, 2]];
        yield 'ids_list' => [['ids' => [1, 2]]];
        yield 'id_list' => [['id' => [1, 2]]];
    }

    #[DataProvider('provideResolvableData')]
    public function testResolveResolvableData(string $data): void
    {
        $contentView = $this->resolver->resolve($data, 'en');

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame($data, $content->getId());
        $this->assertSame('article', $content->getResourceLoaderKey());

        $this->assertSame([
            'id' => $data,
        ], $contentView->getView());
    }

    /**
     * @return iterable<mixed[]>
     */
    public static function provideResolvableData(): iterable
    {
        yield 'string_id' => ['1'];
    }

    public function testCustomResourceLoader(): void
    {
        $contentView = $this->resolver->resolve('1', 'en', ['resourceLoader' => 'custom_article']);

        $content = $contentView->getContent();
        $this->assertInstanceOf(ResolvableResource::class, $content);
        $this->assertSame('1', $content->getId());
        $this->assertSame('custom_article', $content->getResourceLoaderKey());
    }
}
