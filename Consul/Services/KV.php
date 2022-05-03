<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

final class KV implements KVInterface
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function get($key, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'recurse', 'keys', 'separator', 'raw', 'stale', 'consistent', 'default']),
        ];

        return $this->client->get('v1/kv/'.$key, $params);
    }

    public function put($key, $value, array $options = [])
    {
        $params = [
            'body' => (string) $value,
            'query' => OptionsResolver::resolve($options, ['dc', 'flags', 'cas', 'acquire', 'release']),
        ];

        return $this->client->put('v1/kv/'.$key, $params);
    }

    public function delete($key, array $options = [])
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['dc', 'recurse']),
        ];

        return $this->client->delete('v1/kv/'.$key, $params);
    }
}
