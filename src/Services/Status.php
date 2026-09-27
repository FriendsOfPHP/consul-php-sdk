<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Status
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function leader(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/status/leader', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }

    public function peers(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/status/peers', ['query' => OptionsResolver::resolve($options, ['dc'])]);
    }
}
