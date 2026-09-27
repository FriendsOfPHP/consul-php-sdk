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

    public function datacenters(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/datacenters', ['query' => OptionsResolver::resolve($options, ['cached'])]);
    }

    public function nodes(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/nodes', ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'node-meta', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function nodeServices(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/node-services/'.$node, ['query' => OptionsResolver::resolve($options, ['dc', 'filter', 'merge-central-config', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function services(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/services', ['query' => OptionsResolver::resolve($options, ['dc', 'node-meta', 'filter', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/service/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'tag', 'near', 'node-meta', 'filter', 'peer', 'merge-central-config', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function connect(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/connect/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'tag', 'near', 'node-meta', 'filter', 'peer', 'merge-central-config', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function gatewayServices(string $gateway, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/catalog/gateway-services/'.$gateway, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }
}
