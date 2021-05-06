<?php

namespace SensioLabs\Consul\Services;

use InvalidArgumentException;
use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

final class TXN implements TXNInterface
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function put(array $operations = array(), array $options = array())
    {
        $this->validate($operations);

        $params = array(
            'body' => json_encode($operations),
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->put('v1/txn', $params);
    }

    /**
     * Validate Transaction Available Operations
     *
     * @return void
     * @throws InvalidArgumentException
     */
    private function validate(array $operations = array())
    {
        foreach ($operations as $index => $operation) {
            if (!is_int($index)) {
                throw new InvalidArgumentException('Invalid Operations Array!');
            }

            $invalidOperations = array_diff(array_keys($operation), ['KV', 'Node', 'Service', 'Check']);
            if (count($invalidOperations)) {
                throw new InvalidArgumentException('Invalid Operations!');
            }
        }
    }
}
