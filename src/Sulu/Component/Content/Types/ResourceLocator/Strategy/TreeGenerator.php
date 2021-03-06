<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

/**
 * Generates the resource-locator with the full-tree.
 */
class TreeGenerator implements ResourceLocatorGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate($title, $parentPath = null)
    {
        // if parent has no resource create a new tree
        if (null == $parentPath) {
            return '/' . $title;
        }

        // concat parentPath and title to whole tree path
        return $parentPath . '/' . $title;
    }
}
