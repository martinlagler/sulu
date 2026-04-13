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

namespace Sulu\Bundle\AdminBundle\Tests\Unit\CacheWarmer;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\CacheWarmer\SmartContentParamsValidationWarmer;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;

class SmartContentParamsValidationWarmerTest extends TestCase
{
    public function testIsNotOptional(): void
    {
        $loader = $this->createStub(TemplateXmlLoader::class);
        $warmer = new SmartContentParamsValidationWarmer($loader, []);

        $this->assertFalse($warmer->isOptional());
    }

    public function testNoTemplateDirectories(): void
    {
        $loader = $this->createMock(TemplateXmlLoader::class);
        $loader->expects($this->never())->method('load');

        $warmer = new SmartContentParamsValidationWarmer($loader, []);
        $warmer->warmUp('/tmp/cache');
    }

    public function testNonExistentDirectoryIsSkipped(): void
    {
        $loader = $this->createMock(TemplateXmlLoader::class);
        $loader->expects($this->never())->method('load');

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => ['/nonexistent/path/that/does/not/exist'],
            ],
        ]);

        $warmer->warmUp('/tmp/cache');
    }

    public function testValidSmartContentParamsPass(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'valid.xml', 'valid-template', 'articles', [
            'groups' => 'blog,news',
            'template' => 'default',
        ]);

        $formMetadata = $this->buildFormMetadata('valid-template', 'smart_content', 'articles', [
            'provider' => 'articles',
            'groups' => 'blog,news',
            'template' => 'default',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $warmer->warmUp('/tmp/cache');
        $this->addToAssertionCount(1);

        $this->removeTempDir($templateDir);
    }

    public function testDeprecatedTypesParamThrowsForArticles(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles', [
            'types' => 'blog',
        ]);

        $formMetadata = $this->buildFormMetadata('bad-template', 'smart_content', 'my_property', [
            'provider' => 'articles',
            'types' => 'blog',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testDeprecatedStructureTypesParamThrowsForArticles(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles', [
            'structureTypes' => 'default',
        ]);

        $formMetadata = $this->buildFormMetadata('bad-template', 'smart_content', 'my_property', [
            'provider' => 'articles',
            'structureTypes' => 'default',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "structureTypes" is deprecated, rename to "template"');

        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testDeprecatedTypesParamThrowsForArticlesPageTree(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles_page_tree', [
            'types' => 'blog',
        ]);

        $formMetadata = $this->buildFormMetadata('bad-template', 'smart_content', 'my_property', [
            'provider' => 'articles_page_tree',
            'types' => 'blog',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testDeprecatedTypesParamThrowsForPageProvider(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'pages', [
            'types' => 'blog',
        ]);

        $formMetadata = $this->buildFormMetadata('bad-template', 'smart_content', 'my_property', [
            'provider' => 'pages',
            'types' => 'blog',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/param "types" is deprecated, rename to "template"/');
        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testNonSmartContentPropertiesAreIgnored(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'ok.xml', 'ok-template', null, []);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('ok-template');

        $field = new FieldMetadata('title');
        $field->setType('text_line');

        $option = new OptionMetadata();
        $option->setName('types');
        $option->setType(OptionMetadata::TYPE_STRING);
        $option->setValue('something');
        $field->addOption($option);

        $formMetadata->addItem($field);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $warmer->warmUp('/tmp/cache');
        $this->addToAssertionCount(1);

        $this->removeTempDir($templateDir);
    }

    public function testSmartContentWithNoProviderStillValidatesParams(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', null, [
            'types' => 'blog',
        ]);

        $formMetadata = $this->buildFormMetadata('bad-template', 'smart_content', 'my_property', [
            'types' => 'blog',
        ]);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/param "types" is deprecated, rename to "template"/');
        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testSmartContentInSectionIsValidated(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles', [
            'types' => 'blog',
        ]);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('bad-template');

        $section = new SectionMetadata('content');

        $field = new FieldMetadata('my_property');
        $field->setType('smart_content');

        $providerOption = new OptionMetadata();
        $providerOption->setName('provider');
        $providerOption->setType(OptionMetadata::TYPE_STRING);
        $providerOption->setValue('articles');
        $field->addOption($providerOption);

        $typesOption = new OptionMetadata();
        $typesOption->setName('types');
        $typesOption->setType(OptionMetadata::TYPE_STRING);
        $typesOption->setValue('blog');
        $field->addOption($typesOption);

        $section->addItem($field);
        $formMetadata->addItem($section);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testSmartContentInBlockTypeIsValidated(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles', [
            'types' => 'blog',
        ]);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('bad-template');

        $block = new FieldMetadata('content_block');
        $block->setType('block');

        $blockType = new FormMetadata();
        $blockType->setKey('smart_content_type');

        $field = new FieldMetadata('smart_articles');
        $field->setType('smart_content');

        $providerOption = new OptionMetadata();
        $providerOption->setName('provider');
        $providerOption->setType(OptionMetadata::TYPE_STRING);
        $providerOption->setValue('articles');
        $field->addOption($providerOption);

        $typesOption = new OptionMetadata();
        $typesOption->setName('types');
        $typesOption->setType(OptionMetadata::TYPE_STRING);
        $typesOption->setValue('blog');
        $field->addOption($typesOption);

        $blockType->addItem($field);
        $block->addType($blockType);
        $formMetadata->addItem($block);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('param "types" is deprecated, rename to "groups"');

        $warmer->warmUp('/tmp/cache');

        $this->removeTempDir($templateDir);
    }

    public function testMultipleErrorsAreCollected(): void
    {
        $templateDir = $this->createTempTemplateDir();

        $this->createTemplateXml($templateDir, 'bad.xml', 'bad-template', 'articles', [
            'types' => 'blog',
            'structureTypes' => 'default',
        ]);

        $formMetadata = new FormMetadata();
        $formMetadata->setKey('bad-template');

        $field = new FieldMetadata('my_property');
        $field->setType('smart_content');

        $providerOption = new OptionMetadata();
        $providerOption->setName('provider');
        $providerOption->setType(OptionMetadata::TYPE_STRING);
        $providerOption->setValue('articles');
        $field->addOption($providerOption);

        $typesOption = new OptionMetadata();
        $typesOption->setName('types');
        $typesOption->setType(OptionMetadata::TYPE_STRING);
        $typesOption->setValue('blog');
        $field->addOption($typesOption);

        $structureTypesOption = new OptionMetadata();
        $structureTypesOption->setName('structureTypes');
        $structureTypesOption->setType(OptionMetadata::TYPE_STRING);
        $structureTypesOption->setValue('default');
        $field->addOption($structureTypesOption);

        $formMetadata->addItem($field);

        $loader = $this->createStub(TemplateXmlLoader::class);
        $loader->method('load')->willReturn($formMetadata);

        $warmer = new SmartContentParamsValidationWarmer($loader, [
            'pages' => [
                'default_type' => null,
                'directories' => [$templateDir],
            ],
        ]);

        try {
            $warmer->warmUp('/tmp/cache');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
            $this->assertStringContainsString('param "types" is deprecated, rename to "groups"', $message);
            $this->assertStringContainsString('param "structureTypes" is deprecated, rename to "template"', $message);
        }

        $this->removeTempDir($templateDir);
    }

    /**
     * @param array<string, string> $params
     */
    private function buildFormMetadata(
        string $templateKey,
        string $fieldType,
        string $propertyName,
        array $params,
    ): FormMetadata {
        $formMetadata = new FormMetadata();
        $formMetadata->setKey($templateKey);

        $field = new FieldMetadata($propertyName);
        $field->setType($fieldType);

        foreach ($params as $name => $value) {
            $option = new OptionMetadata();
            $option->setName($name);
            $option->setType(OptionMetadata::TYPE_STRING);
            $option->setValue($value);
            $field->addOption($option);
        }

        $formMetadata->addItem($field);

        return $formMetadata;
    }

    private function createTempTemplateDir(): string
    {
        $dir = \sys_get_temp_dir() . '/sulu_test_' . \uniqid('', true);
        \mkdir($dir, 0777, true);

        return $dir;
    }

    /**
     * @param array<string, string> $params
     */
    private function createTemplateXml(
        string $dir,
        string $filename,
        string $templateKey,
        ?string $provider,
        array $params,
    ): void {
        $paramsXml = '';
        if (null !== $provider) {
            $params = \array_merge(['provider' => $provider], $params);
        }

        if (\count($params) > 0) {
            $paramLines = [];
            foreach ($params as $name => $value) {
                $paramLines[] = \sprintf('                <param name="%s" value="%s"/>', $name, $value);
            }
            $paramsXml = "<params>\n" . \implode("\n", $paramLines) . "\n            </params>";
        }

        $xml = <<<XML
<?xml version="1.0" ?>
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">
    <key>{$templateKey}</key>
    <view>test</view>
    <controller>TestController::indexAction</controller>
    <cacheLifetime>0</cacheLifetime>
    <meta>
        <title lang="en">Test</title>
    </meta>
    <properties>
        <property name="title" type="text_line" mandatory="true">
            <meta><title lang="en">Title</title></meta>
        </property>
        <property name="my_property" type="smart_content">
            <meta><title lang="en">Smart Content</title></meta>
            {$paramsXml}
        </property>
    </properties>
</template>
XML;

        \file_put_contents($dir . '/' . $filename, $xml);
    }

    private function removeTempDir(string $dir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                \rmdir((string) $file->getRealPath());
            } else {
                \unlink((string) $file->getRealPath());
            }
        }

        \rmdir($dir);
    }
}
