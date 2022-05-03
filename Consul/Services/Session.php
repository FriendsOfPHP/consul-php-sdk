<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

final class Session
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function create($body = null, array $options = [])
    {
        $params = [
            'body' => $body,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/create', $params);
    }

    public function destroy($sessionId, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/destroy/'.$sessionId, $params);
    }

    public function info($sessionId, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/info/'.$sessionId, $params);
    }

    public function node($node, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/node/'.$node, $params);
    }

    public function all(array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->get('/v1/session/list', $params);
    }

    public function renew($sessionId, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/session/renew/'.$sessionId, $params);
    }
}
