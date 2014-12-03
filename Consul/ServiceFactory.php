<?php

namespace SensioLabs\Consul;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;

class ServiceFactory
{
    private static $services = array(
        'agent' => 'SensioLabs\Consul\Services\Agent',
        'catalog' => 'SensioLabs\Consul\Services\Catalog',
        'health' => 'SensioLabs\Consul\Services\Health',
        'kv' => 'SensioLabs\Consul\Services\KV',
        'session' => 'SensioLabs\Consul\Services\Session',
    );

    private $client;

    public function __construct(array $options = array(), LoggerInterface $logger = null, GuzzleClient $guzzleClient = null)
    {
        $this->client = new Client($options, $logger, $guzzleClient);
    }

    public function get($service)
    {
        if (!array_key_exists($service, self::$services)) {
            throw new \InvalidArgumentException(sprintf('The service "%s" is not available. Pick one among "%s".', $service, implode('", "', array_keys(self::$services))));
        }

        $class = self::$services[$service];

        return new $class($this->client);
    }
}
