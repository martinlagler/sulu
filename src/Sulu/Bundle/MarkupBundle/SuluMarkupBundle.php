<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle;

use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\ParserCompilerPass;
use Sulu\Bundle\MarkupBundle\DependencyInjection\CompilerPass\TagCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluMarkupBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ParserCompilerPass());
        $container->addCompilerPass(new TagCompilerPass());
    }
}
