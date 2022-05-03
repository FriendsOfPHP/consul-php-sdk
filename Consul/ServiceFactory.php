<?php

namespace SensioLabs\Consul;

use Psr\Log\LoggerInterface;
use SensioLabs\Consul\Services\Agent;
use SensioLabs\Consul\Services\Catalog;
use SensioLabs\Consul\Services\Health;
use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ServiceFactory
{
    private const SERVICES = [
        Agent::class,
        Catalog::class,
        Health::class,
        Session::class,
        KV::class,
    ];

    private Client $client;

    public function __construct(array $options = [], LoggerInterface $logger = null, HttpClientInterface $httpClient = null)
    {
        $this->client = new Client($options, $logger, $httpClient);
    }

    public function get($service)
    {
        if (!\in_array($service, self::SERVICES, true)) {
            throw new \InvalidArgumentException(sprintf('The service "%s" is not available. Pick one among "%s".', $service, implode('", "', self::SERVICES)));
        }

        $class = self::SERVICES[$service];

        return new $class($this->client);
    }
}
