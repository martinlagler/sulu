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

namespace Sulu\Bundle\AdminBundle\CacheWarmer;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\TemplateXmlLoader;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class SmartContentParamsValidationWarmer implements CacheWarmerInterface
{
    private const DEPRECATED_PARAMS = ['types', 'structureTypes'];

    private const ARTICLE_PROVIDERS = [
        'articles',
        'articles_page_tree',
    ];

    /**
     * @param array<string, array{
     *     default_type: string|null,
     *     directories: array<string>,
     * }> $templateDirectories
     */
    public function __construct(
        private TemplateXmlLoader $templateXmlLoader,
        private array $templateDirectories,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $errors = [];

        foreach ($this->templateDirectories as $config) {
            $directories = \array_filter(
                $config['directories'],
                fn (string $directory) => \file_exists($directory),
            );

            if (0 === \count($directories)) {
                continue;
            }

            $finder = (new Finder())->in($directories)->name('*.xml');

            foreach ($finder as $file) {
                $formMetadata = $this->templateXmlLoader->load($file->getPathName());
                $templateKey = $formMetadata->getKey();

                $this->collectDeprecatedParams(
                    $formMetadata->getItems(),
                    $templateKey,
                    $file->getPathName(),
                    $errors,
                );
            }
        }

        if (\count($errors) > 0) {
            throw new \RuntimeException(
                "Deprecated smart_content param names found in template XML files:\n\n"
                . \implode("\n", $errors)
                . "\n\nPlease rename the deprecated params to their replacements."
            );
        }

        return [];
    }

    /**
     * @param ItemMetadata[] $items
     * @param string[] $errors
     */
    private function collectDeprecatedParams(
        array $items,
        string $templateKey,
        string $filePath,
        array &$errors,
    ): void {
        foreach ($items as $item) {
            if ($item instanceof SectionMetadata) {
                $this->collectDeprecatedParams($item->getItems(), $templateKey, $filePath, $errors);
                continue;
            }

            if (!$item instanceof FieldMetadata) {
                continue;
            }

            foreach ($item->getTypes() as $type) {
                $this->collectDeprecatedParams($type->getItems(), $templateKey, $filePath, $errors);
            }

            if ('smart_content' !== $item->getType()) {
                continue;
            }

            $providerOption = $item->findOption('provider');
            $provider = $providerOption?->getValue();
            $isArticleProvider = \is_string($provider) && \in_array($provider, self::ARTICLE_PROVIDERS, true);

            foreach (self::DEPRECATED_PARAMS as $deprecated) {
                if (null === $item->findOption($deprecated)) {
                    continue;
                }

                $replacement = match ($deprecated) {
                    'types' => $isArticleProvider ? 'groups' : 'template',
                    'structureTypes' => 'template',
                };

                $errors[] = \sprintf(
                    '- Template "%s", property "%s": param "%s" is deprecated, rename to "%s" (file: %s)',
                    $templateKey,
                    $item->getName(),
                    $deprecated,
                    $replacement,
                    $filePath,
                );
            }
        }
    }
}
