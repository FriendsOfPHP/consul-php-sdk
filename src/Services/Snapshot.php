<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Snapshot
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function save(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/snapshot', ['query' => OptionsResolver::resolve($options, ['dc', 'stale'])]);
    }

    public function restore(string $snapshot, array $options = []): ConsulResponse
    {
        $params = [
            'body' => $snapshot,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/snapshot', $params);
    }
}
