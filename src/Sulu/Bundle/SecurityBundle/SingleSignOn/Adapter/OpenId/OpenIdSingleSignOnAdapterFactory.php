<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId;

use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterFactoryInterface;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @final
 *
 * @experimental
 */
class OpenIdSingleSignOnAdapterFactory implements SingleSignOnAdapterFactoryInterface
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function createAdapter(#[\SensitiveParameter] array $dsn): SingleSignOnAdapterInterface
    {
        $protocol = $dsn['query']['no-tls'] ? 'http' : 'https';

        $endpoint = $protocol . '://' . $dsn['host'] . ':' . $dsn['port'] . $dsn['path'];
        $clientId = $dsn['user'] ?? '';
        $clientSecret = $dsn['pass'] ?? '';

        return new OpenIdSingleSignOnAdapter(
            $this->httpClient,
            $endpoint,
            $clientId,
            $clientSecret,
        );
    }

    public static function getName(): string
    {
        return 'openid';
    }
}
