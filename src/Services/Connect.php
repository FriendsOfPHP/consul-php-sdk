<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Connect
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function listCARoots(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/connect/ca/roots', ['query' => OptionsResolver::resolve($options, ['pem', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readCAConfiguration(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/connect/ca/configuration', ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function updateCAConfiguration(array $configuration): ConsulResponse
    {
        return $this->client->put('/v1/connect/ca/configuration', ['json' => $configuration]);
    }

    public function listIntentions(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/connect/intentions', ['query' => OptionsResolver::resolve($options, ['filter', 'index', 'wait', 'stale', 'consistent'])]);
    }

    public function readIntention(string $source, string $destination, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/connect/intentions/exact', ['query' => ['source' => $source, 'destination' => $destination] + OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent'])]);
    }

    public function upsertIntention(string $source, string $destination, array $intention): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $intention,
            'query' => ['source' => $source, 'destination' => $destination],
        ];

        return $this->client->put('/v1/connect/intentions/exact', $params);
    }

    public function deleteIntention(string $source, string $destination): ConsulResponse
    {
        return $this->client->delete('/v1/connect/intentions/exact', ['query' => ['source' => $source, 'destination' => $destination]]);
    }

    public function checkIntention(string $source, string $destination): ConsulResponse
    {
        return $this->client->get('/v1/connect/intentions/check', ['query' => ['source' => $source, 'destination' => $destination]]);
    }

    public function matchIntentions(string $by, string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/connect/intentions/match', ['query' => ['by' => $by, 'name' => $name] + OptionsResolver::resolve($options, ['index', 'wait', 'stale', 'consistent', 'cached'])]);
    }
}
