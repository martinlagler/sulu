<?php

namespace Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter;

class OpenIdSingleSignOnAdapterFactory implements SingleSign
{
    public function getName(): string
    {
        return 'openid';
    }

    public function createAdapter(array $dsn): SingleSignOnAdapterInterface
    {
        return new OpenIdSingleSignOnAdapterFactory();
    }
}
