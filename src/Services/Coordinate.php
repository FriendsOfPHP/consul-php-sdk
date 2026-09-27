<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Coordinate
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function datacenters(): ConsulResponse
    {
        return $this->client->get('/v1/coordinate/datacenters');
    }

    public function nodes(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/coordinate/nodes', ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/coordinate/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function update(array $coordinate, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $coordinate,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/coordinate/update', $params);
    }
}
