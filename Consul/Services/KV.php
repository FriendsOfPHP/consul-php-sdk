<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

final class KV implements KVInterface
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function client()
    {
        return $this->client;
    }

    public function get($key, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc', 'recurse', 'keys', 'separator', 'raw', 'stale', 'consistent', 'default')),
        );

        return $this->client->get('v1/kv/'.$key, $params);
    }

    public function put($key, $value, array $options = array())
    {
        $params = array(
            'body' => (string) $value,
            'query' => OptionsResolver::resolve($options, array('dc', 'flags', 'cas', 'acquire', 'release')),
        );

        return $this->client->put('v1/kv/'.$key, $params);
    }

    public function delete($key, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc', 'recurse')),
        );

        return $this->client->delete('v1/kv/'.$key, $params);
    }
}
