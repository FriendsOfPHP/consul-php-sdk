<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Config
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function apply(array $entry, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $entry,
            'query' => OptionsResolver::resolve($options, ['dc', 'cas']),
        ];

        return $this->client->put('/v1/config', $params);
    }

    public function read(string $kind, string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/config/'.$kind.'/'.$name, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function list(string $kind, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/config/'.$kind, ['query' => OptionsResolver::resolve($options, ['dc', 'filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function delete(string $kind, string $name, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/config/'.$kind.'/'.$name, ['query' => OptionsResolver::resolve($options, ['dc', 'cas'])]);
    }
}
