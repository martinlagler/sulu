<?php

namespace Sulu\Bundle\SecurityBundle\SingleSignOn;

interface SingleSignOnAdapterFactoryInterface
{
    /**
     * @param array{
     *     scheme: string,
     *     host: string,
     *     port?: int,
     *     user?: string,
     *     pass?: string,
     *     path?: string,
     *     query: array<string, string>,
     *     fragment?: string,
     * } $dsn
     */
    public function createAdapter(array $dsn): SingleSignOnAdapterInterface;

    /**
     * Returns the expected DSN scheme for this adapter.
     */
    public static function getName(): string;
}
