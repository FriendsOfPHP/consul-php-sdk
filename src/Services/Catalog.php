<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Catalog
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function register(array $node): ConsulResponse
    {
        return $this->client->put('/v1/catalog/register', ['json' => $node]);
    }

    public function deregister(array $node): ConsulResponse
    {
        return $this->client->put('/v1/catalog/deregister', ['json' => $node]);
    }

    public function datacenters(): ConsulResponse
    {
        return $this->client->get('/v1/catalog/datacenters');
    }

    public function nodes(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/nodes', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function services(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/services', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/service/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'tag'])]);
    }
}
