<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class TXN
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function put(array $operations = [], array $options = []): ConsulResponse
    {
        $this->validate($operations);

        $params = [
            'json' => $operations,
            'query' => OptionsResolver::resolve($options, ['dc']),
        ];

        return $this->client->put('v1/txn', $params);
    }

    /**
     * Validate Transaction Available Operations.
     *
     * @throws \InvalidArgumentException
     */
    private function validate(array $operations = []): void
    {
        if (!array_is_list($operations)) {
            throw new \InvalidArgumentException('Invalid Operations Array!');
        }

        foreach ($operations as $operation) {
            if (array_diff(array_keys($operation), ['KV', 'Node', 'Service', 'Check'])) {
                throw new \InvalidArgumentException('Invalid Operations!');
            }
        }
    }
}
