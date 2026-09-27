<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Peering
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function generateToken(array $peering): ConsulResponse
    {
        return $this->client->post('/v1/peering/token', ['json' => $peering]);
    }

    public function establish(array $peering): ConsulResponse
    {
        return $this->client->post('/v1/peering/establish', ['json' => $peering]);
    }

    public function read(string $name, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/peering/'.$name, ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'consistent'])]);
    }

    public function delete(string $name): ConsulResponse
    {
        return $this->client->delete('/v1/peering/'.$name);
    }

    public function list(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/peerings', ['query' => OptionsResolver::resolve($options, ['index', 'wait', 'consistent', 'cached'])]);
    }

    public function listExportedServices(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/exported-services', ['query' => OptionsResolver::resolve($options, ['index', 'wait'])]);
    }

    public function listImportedServices(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/imported-services', ['query' => OptionsResolver::resolve($options, ['index', 'wait'])]);
    }
}
