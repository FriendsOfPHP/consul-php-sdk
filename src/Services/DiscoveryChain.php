<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class DiscoveryChain
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function read(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/discovery-chain/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'compile-dc', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    /**
     * Same as readDiscoveryChain(), with override parameters (OverrideConnectTimeout, OverrideProtocol, OverrideMeshGateway).
     */
    public function readWithOverrides(string $service, array $overrides, array $options = []): ConsulResponse
    {
        $params = [
            // Consul expects a JSON object, even when empty
            'json' => (object) $overrides,
            'query' => OptionsResolver::resolve($options, ['dc', 'compile-dc', 'index', 'wait', 'stale', 'consistent', 'cached']),
        ];

        return $this->client->post('/v1/discovery-chain/'.$service, $params);
    }
}
