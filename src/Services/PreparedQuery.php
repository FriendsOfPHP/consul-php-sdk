<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class PreparedQuery
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function create(array $query, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $query,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->post('/v1/query', $params);
    }

    public function list(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/query', ['query' => OptionsResolver::resolve($options, ['dc', 'stale', 'consistent'])]);
    }

    public function read(string $queryId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/query/'.$queryId, ['query' => OptionsResolver::resolve($options, ['dc', 'stale', 'consistent'])]);
    }

    public function update(string $queryId, array $query, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $query,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('/v1/query/'.$queryId, $params);
    }

    public function delete(string $queryId, array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/query/'.$queryId, ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    /**
     * @param string $queryIdOrName The query ID, or its name
     */
    public function execute(string $queryIdOrName, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/query/'.$queryIdOrName.'/execute', ['query' => OptionsResolver::resolve($options, ['dc', 'near', 'limit', 'connect', 'stale', 'consistent', 'cached'])]);
    }

    /**
     * @param string $queryIdOrName The query ID, or its name
     */
    public function explain(string $queryIdOrName, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/query/'.$queryIdOrName.'/explain', ['query' => OptionsResolver::resolve($options, ['dc', 'stale', 'consistent'])]);
    }
}
