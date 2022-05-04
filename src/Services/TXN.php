<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final class TXN
{
    private Client $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
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
        foreach ($operations as $index => $operation) {
            if (!\is_int($index)) {
                throw new \InvalidArgumentException('Invalid Operations Array!');
            }

            $invalidOperations = array_diff(array_keys($operation), ['KV', 'Node', 'Service', 'Check']);
            if (\count($invalidOperations)) {
                throw new \InvalidArgumentException('Invalid Operations!');
            }
        }
    }
}
