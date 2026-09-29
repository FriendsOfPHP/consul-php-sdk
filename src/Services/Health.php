<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Health
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function checks(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/checks/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'node-meta', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/service/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'tag', 'node-meta', 'passing', 'filter', 'peer', 'merge-central-config', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function connect(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/connect/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'tag', 'node-meta', 'passing', 'filter', 'peer', 'merge-central-config', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function ingress(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/ingress/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'tag', 'node-meta', 'passing', 'filter', 'merge-central-config', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function state(string $state, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/state/'.$state, ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'node-meta', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }
}
