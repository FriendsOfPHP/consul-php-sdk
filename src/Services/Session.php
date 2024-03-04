<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final class Session
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
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
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/destroy/'.$sessionId, $params);
    }

    public function info(string $sessionId, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/info/'.$sessionId, $params);
    }

    public function node(string $node, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/node/'.$node, $params);
    }

    public function all(array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/list', $params);
    }

    public function renew(string $sessionId, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/renew/'.$sessionId, $params);
    }
}
