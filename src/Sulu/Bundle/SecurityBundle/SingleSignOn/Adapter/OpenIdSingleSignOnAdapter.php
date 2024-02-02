<?php

namespace Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter;

class OpenIdSingleSignOnAdapter implements SingleSignOnAdapterInterface
{
    public function getName(): string
    {
        return 'openid';
    }

    public function createAdapter(array $dsn): SingleSignOnAdapterInterface
    {
        return new OpenIdSingleSignOnAdapter();
    }
}
