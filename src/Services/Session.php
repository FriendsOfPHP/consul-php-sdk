<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Session
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function create(array $session = [], array $options = []): ConsulResponse
    {
        $params = [
            'json' => $session,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/create', $params);
    }

    public function destroy(string $sessionId, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/session/destroy/'.$sessionId, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function info(string $sessionId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/session/info/'.$sessionId, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/session/node/'.$node, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function all(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/session/list', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function renew(string $sessionId, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/session/renew/'.$sessionId, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }
}
