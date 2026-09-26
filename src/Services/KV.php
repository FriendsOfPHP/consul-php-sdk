<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class KV
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function get(string $key, array $options = []): ConsulResponse
    {
        return $this->client->get('v1/kv/'.$key, ['query' => OptionsResolver::resolve($options, ['dc', 'recurse', 'keys', 'separator', 'raw', 'stale', 'consistent', 'default'])]);
    }

    public function put(string $key, mixed $value, array $options = []): ConsulResponse
    {
        $params = [
            'body' => $value,
            'query' => OptionsResolver::resolve($options, ['dc', 'flags', 'cas', 'acquire', 'release']),
        ];

        return $this->client->put('v1/kv/'.$key, $params);
    }

    public function delete(string $key, array $options = []): ConsulResponse
    {
        return $this->client->delete('v1/kv/'.$key, ['query' => OptionsResolver::resolve($options, ['dc', 'recurse'])]);
    }
}
