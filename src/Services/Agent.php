<?php

namespace Consul\Services;

use Consul\Client;
use Consul\ClientInterface;
use Consul\ConsulResponse;
use Consul\OptionsResolver;

final readonly class Agent
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
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
        return $this->client->get('/v1/agent/members', ['query' => OptionsResolver::resolve($options, ['wan'])]);
    }

    public function self(): ConsulResponse
    {
        return $this->client->get('/v1/agent/self');
    }

    public function join(string $address, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/join/'.$address, ['query' => OptionsResolver::resolve($options, ['wan'])]);
    }

    public function forceLeave(string $node): ConsulResponse
    {
        return $this->client->get('/v1/agent/force-leave/'.$node);
    }

    public function registerCheck(array $check): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/register', ['json' => $check]);
    }

    public function deregisterCheck(string $checkId): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/deregister/'.$checkId);
    }

    public function passCheck(string $checkId, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/pass/'.$checkId, ['query' => OptionsResolver::resolve($options, ['note'])]);
    }

    public function warnCheck(string $checkId, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/warn/'.$checkId, ['query' => OptionsResolver::resolve($options, ['note'])]);
    }

    public function failCheck(string $checkId, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/fail/'.$checkId, ['query' => OptionsResolver::resolve($options, ['note'])]);
    }

    public function registerService(array $service): ConsulResponse
    {
        return $this->client->put('/v1/agent/service/register', ['json' => $service]);
    }

    public function deregisterService(string $serviceId): ConsulResponse
    {
        return $this->client->put('/v1/agent/service/deregister/'.$serviceId);
    }
}
