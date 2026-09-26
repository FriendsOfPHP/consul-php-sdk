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
        return $this->client->get('/v1/health/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function checks(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/checks/'.$service, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/service/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'tag', 'passing'])]);
    }

    public function state(string $state, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/health/state/'.$state, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }
}
