<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Operator
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function readRaftConfiguration(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/raft/configuration', ['query' => OptionsResolver::resolve($options, ['dc', 'stale'])]);
    }

    public function transferRaftLeader(array $options = []): ConsulResponse
    {
        return $this->client->post('/v1/operator/raft/transfer-leader', ['query' => OptionsResolver::resolve($options, ['dc', 'id'])]);
    }

    /**
     * Either the "id" or the "address" option is required.
     */
    public function deleteRaftPeer(array $options = []): ConsulResponse
    {
        return $this->client->delete('/v1/operator/raft/peer', ['query' => OptionsResolver::resolve($options, ['dc', 'id', 'address', 'dc'])]);
    }

    public function listKeys(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/keyring', ['query' => OptionsResolver::resolve($options, ['dc', 'relay-factor', 'local-only'])]);
    }

    public function installKey(string $key, array $options = []): ConsulResponse
    {
        $params = [
            'json' => ['Key' => $key],
            'query' => OptionsResolver::resolve($options, ['dc', 'relay-factor']),
        ];

        return $this->client->post('/v1/operator/keyring', $params);
    }

    public function useKey(string $key, array $options = []): ConsulResponse
    {
        $params = [
            'json' => ['Key' => $key],
            'query' => OptionsResolver::resolve($options, ['dc', 'relay-factor']),
        ];

        return $this->client->put('/v1/operator/keyring', $params);
    }

    public function removeKey(string $key, array $options = []): ConsulResponse
    {
        $params = [
            'json' => ['Key' => $key],
            'query' => OptionsResolver::resolve($options, ['dc', 'relay-factor']),
        ];

        return $this->client->delete('/v1/operator/keyring', $params);
    }

    public function readAutopilotConfiguration(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/autopilot/configuration', ['query' => OptionsResolver::resolve($options, ['dc', 'stale'])]);
    }

    public function updateAutopilotConfiguration(array $configuration, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $configuration,
            'query' => OptionsResolver::resolve($options, ['dc', 'cas']),
        ];

        return $this->client->put('/v1/operator/autopilot/configuration', $params);
    }

    public function readAutopilotHealth(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/autopilot/health', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function readAutopilotState(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/autopilot/state', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function readUsage(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/operator/usage', ['query' => OptionsResolver::resolve($options, ['dc', 'global', 'index', 'wait', 'stale', 'consistent'])]);
    }
}
