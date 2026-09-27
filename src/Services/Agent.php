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

    public function host(): ConsulResponse
    {
        return $this->client->get('/v1/agent/host');
    }

    public function version(): ConsulResponse
    {
        return $this->client->get('/v1/agent/version');
    }

    public function checks(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/checks', ['query' => OptionsResolver::resolve($options, ['filter'])]);
    }

    public function services(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/services', ['query' => OptionsResolver::resolve($options, ['filter'])]);
    }

    public function service(string $serviceId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/service/'.$serviceId, ['query' => OptionsResolver::resolve($options, ['hash', 'wait'])]);
    }

    public function members(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/members', ['query' => OptionsResolver::resolve($options, ['wan'])]);
    }

    public function self(): ConsulResponse
    {
        return $this->client->get('/v1/agent/self');
    }

    public function reload(): ConsulResponse
    {
        return $this->client->put('/v1/agent/reload');
    }

    public function maintenance(bool $enable, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/maintenance', ['query' => ['enable' => $enable ? 'true' : 'false'] + OptionsResolver::resolve($options, ['reason'])]);
    }

    public function metrics(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/metrics', ['query' => OptionsResolver::resolve($options, ['format'])]);
    }

    public function join(string $address, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/join/'.$address, ['query' => OptionsResolver::resolve($options, ['wan'])]);
    }

    public function leave(): ConsulResponse
    {
        return $this->client->put('/v1/agent/leave');
    }

    public function forceLeave(string $node, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/force-leave/'.$node, ['query' => OptionsResolver::resolve($options, ['prune', 'wan'])]);
    }

    public function updateToken(string $type, array $token): ConsulResponse
    {
        return $this->client->put('/v1/agent/token/'.$type, ['json' => $token]);
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

    public function updateCheck(string $checkId, array $check): ConsulResponse
    {
        return $this->client->put('/v1/agent/check/update/'.$checkId, ['json' => (object) $check]);
    }

    public function registerService(array $service, array $options = []): ConsulResponse
    {
        $params = [
            'json' => $service,
            'query' => OptionsResolver::resolve($options, ['replace-existing-checks']),
        ];

        return $this->client->put('/v1/agent/service/register', $params);
    }

    public function deregisterService(string $serviceId): ConsulResponse
    {
        return $this->client->put('/v1/agent/service/deregister/'.$serviceId);
    }

    public function serviceMaintenance(string $serviceId, bool $enable, array $options = []): ConsulResponse
    {
        return $this->client->put('/v1/agent/service/maintenance/'.$serviceId, ['query' => ['enable' => $enable ? 'true' : 'false'] + OptionsResolver::resolve($options, ['reason'])]);
    }

    /**
     * Returns HTTP 429 (warning) or 503 (critical) when checks are not passing:
     * the client turns them into a ClientException or a ServerException.
     */
    public function healthServiceById(string $serviceId, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/health/service/id/'.$serviceId, ['query' => OptionsResolver::resolve($options, ['format'])]);
    }

    /**
     * Returns HTTP 429 (warning) or 503 (critical) when checks are not passing:
     * the client turns them into a ClientException or a ServerException.
     */
    public function healthServiceByName(string $serviceName, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/health/service/name/'.$serviceName, ['query' => OptionsResolver::resolve($options, ['format'])]);
    }

    public function connectAuthorize(array $authorization): ConsulResponse
    {
        return $this->client->post('/v1/agent/connect/authorize', ['json' => $authorization]);
    }

    public function connectCARoots(array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/connect/ca/roots', ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }

    public function connectCALeaf(string $service, array $options = []): ConsulResponse
    {
        return $this->client->get('/v1/agent/connect/ca/leaf/'.$service, ['query' => OptionsResolver::resolve($options, ['dc', 'index', 'wait', 'stale', 'consistent', 'cached'])]);
    }
}
