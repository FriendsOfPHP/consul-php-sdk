<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final class Health
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/health/node/'.$node, $params);
    }

    public function checks(string $service, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/health/checks/'.$service, $params);
    }

    public function service(string $service, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'tag', 'passing']),
        ];

        return $this->client->get('/v1/health/service/'.$service, $params);
    }

    public function state(string $state, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/health/state/'.$state, $params);
    }
}
