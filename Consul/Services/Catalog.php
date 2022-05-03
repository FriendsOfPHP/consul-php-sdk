<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

final class Catalog
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function register($node)
    {
        $params = [
            'body' => (string) $node,
        ];

        return $this->client->put('/v1/catalog/register', $params);
    }

    public function deregister($node)
    {
        $params = [
            'body' => (string) $node,
        ];

        return $this->client->put('/v1/catalog/deregister', $params);
    }

    public function datacenters()
    {
        return $this->client->get('/v1/catalog/datacenters');
    }

    public function nodes(array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/nodes', $params);
    }

    public function node($node, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/node/'.$node, $params);
    }

    public function services(array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/catalog/services', $params);
    }

    public function service($service, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'tag']),
        ];

        return $this->client->get('/v1/catalog/service/'.$service, $params);
    }
}
