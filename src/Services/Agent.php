<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final class Agent
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function checks(): ConsulResponse
    {
        return $this->client->get('/v1/agent/checks');
    }

    public function services(): ConsulResponse
    {
        return $this->client->get('/v1/agent/services');
    }

    public function members(array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['wan']),
        ];

        return $this->client->get('/v1/agent/members', $params);
    }

    public function self(): ConsulResponse
    {
        return $this->client->get('/v1/agent/self');
    }

    public function join(string $address, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['wan']),
        ];

        return $this->client->get('/v1/agent/join/'.$address, $params);
    }

    public function forceLeave(string $node): ConsulResponse
    {
        return $this->client->get('/v1/agent/force-leave/'.$node);
    }

    public function registerCheck(array $check): ConsulResponse
    {
        $params = [
            'json' => $check,
        ];

        return $this->client->put('/v1/agent/check/register', $params);
    }

    public function deregisterCheck(string $checkId): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/deregister/'.$checkId);
    }

    public function passCheck(string $checkId, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['note']),
        ];

        return $this->client->put('/v1/agent/check/pass/'.$checkId, $params);
    }

    public function warnCheck(string $checkId, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['note']),
        ];

        return $this->client->put('/v1/agent/check/warn/'.$checkId, $params);
    }

    public function failCheck(string $checkId, array $options = []): ConsulResponse
    {
        $params = [
            'query' => OptionsResolver::resolve($options, ['note']),
        ];

        return $this->client->put('/v1/agent/check/fail/'.$checkId, $params);
    }

    public function registerService(array $service): ConsulResponse
    {
        $params = [
            'json' => $service,
        ];

        return $this->client->put('/v1/agent/service/register', $params);
    }

    public function deregisterService(string $serviceId): ConsulResponse
    {
        return $this->client->put('/v1/agent/service/deregister/'.$serviceId);
    }
}
