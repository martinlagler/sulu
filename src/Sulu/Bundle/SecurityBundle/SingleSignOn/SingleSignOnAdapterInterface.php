<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

use Symfony\Component\HttpFoundation\Request;

/**
 * @experimental
 */
interface SingleSignOnAdapterInterface
{
    public function generateLoginUrl(Request $request, string $redirectUrl): string;
}
