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

use Ramsey\Uuid\Uuid;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @experimental
 */
class OpenIdSingleSignOnAdapter implements SingleSignOnAdapterInterface
{
    private const OPEN_ID_ATTRIBUTES = '_sulu_security_open_id_attributes';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $endpoint,
        private string $clientId,
        #[\SensitiveParameter] private string $clientSecret,
    ) {
    }

    public function generateLoginUrl(Request $request, string $redirectUrl): string
    {
        $openIdConfiguration = $this->httpClient->request('GET', $this->endpoint)->toArray();
        $authorizationEndpoint = $openIdConfiguration['authorization_endpoint'] ?? null;
        if (!$authorizationEndpoint) {
            throw new HttpException(504, 'No authorization_endpoint found in OpenId configuration from: ' . $this->endpoint);
        }

        $authorizationObject = $this->generateAuthorizationUrl(
            $authorizationEndpoint,
            $redirectUrl,
        );

        $request->getSession()->set(self::OPEN_ID_ATTRIBUTES, $authorizationObject['attributes']);

        return $authorizationObject['url'];
    }

    /**
     * @return array{
     *     url: string,
     *     attributes: array<string, string|int>,
     * }
     */
    private function generateAuthorizationUrl(
        string $authenticationEndpoint,
        string $redirectUrl,
        ?string $state = null,
        ?string $nonce = null,
        ?string $codeVerifier = null,
        ?string $codeChallengeMethod = null,
    ): array {
        $state ??= Uuid::uuid4()->toString();
        $attributes['state'] = $state;
        $nonce ??= Uuid::uuid4()->toString();

        $query = [
            'response_type' => 'code',
            'redirect_uri' => $redirectUrl,
            'scope' => 'openid email phone profile address',
            'client_id' => $this->clientId,
            'state' => $state,
            'nonce' => $nonce,
        ];

        if ($codeChallengeMethod) {
            $codeVerifier ??= \base64_encode(\random_bytes(32));
            $codeChallenge = \base64_encode(\hash(match ($codeChallengeMethod) {
                'S256' => 'sha256',
                default => throw new \RuntimeException('Invalid code challenge method'),
            }, $codeVerifier, true));
            $codeChallenge = \rtrim($codeChallenge, '=');
            $codeChallenge = \urlencode($codeChallenge);

            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = $codeChallengeMethod;

            $attributes['codeVerifier'] = $codeVerifier;
        }

        return [
            'url' => $authenticationEndpoint . '?' . \http_build_query($query),
            'attributes' => $attributes,
        ];
    }
}
