<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\ConsulResponse;
use SensioLabs\Consul\OptionsResolver;

final class Catalog
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function register(array $node): ConsulResponse
    {
        $params = [
            'json' => $node,
        ];

        return $this->client->put('/v1/catalog/register', $params);
    }

    public function deregister(array $node): ConsulResponse
    {
        $params = [
            'json' => $node,
        ];

        return $this->client->put('/v1/catalog/deregister', $params);
    }

    public function datacenters(): ConsulResponse
    {
        return $this->client->get('/v1/catalog/datacenters');
    }

    public function nodes(array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/nodes', $params);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/node/'.$node, $params);
    }

    public function services(array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/services', $params);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'tag']),
        ];

        return $this->client->get('/v1/catalog/service/'.$service, $params);
    }
}
