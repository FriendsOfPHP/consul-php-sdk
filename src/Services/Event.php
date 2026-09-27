<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Event
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function fire(string $name, ?string $payload = null, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'node', 'service', 'tag']),
        ];

        if (null !== $payload) {
            $params['body'] = $payload;
        }

        return $this->client->put('/v1/event/fire/'.$name, $params);
    }

    public function list(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/event/list', ['query' => OptionsResolver::resolve($options, ['name', 'node', 'service', 'tag', 'index', 'wait'])]);
    }
}
