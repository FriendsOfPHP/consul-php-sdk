<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

class Agent
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function checks()
    {
        return $this->client->get('/v1/agent/checks');
    }

    public function services()
    {
        return $this->client->get('/v1/agent/services');
    }

    public function members(array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('wan')),
        );

        return $this->client->get('/v1/agent/members', $params);
    }

    public function self()
    {
        return $this->client->get('/v1/agent/self');
    }

    public function join($address, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('wan')),
        );

        return $this->client->get('/v1/agent/join/'.$address, $params);
    }

    public function forceLeave($node)
    {
        return $this->client->get('/v1/agent/force-leave/'.$node);
    }

    public function registerCheck($check)
    {
        $params = array(
            'body' => $check,
        );

        return $this->client->put('/v1/agent/check/register', $params);
    }

    public function deregisterCheck($checkId)
    {
        return $this->client->put('/v1/agent/check/deregister/'.$checkId);
    }

    public function passCheck($checkId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('note')),
        );

        return $this->client->put('/v1/agent/check/pass/'.$checkId, $params);
    }

    public function warnCheck($checkId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('note')),
        );

        return $this->client->put('/v1/agent/check/warn/'.$checkId, $params);
    }

    public function failCheck($checkId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('note')),
        );

        return $this->client->put('/v1/agent/check/fail/'.$checkId, $params);
    }

    public function registerService($service)
    {
        $params = array(
            'body' => $service,
        );

        return $this->client->put('/v1/agent/service/register', $params);
    }

    public function deregisterService($serviceId)
    {
        return $this->client->put('/v1/agent/service/deregister/'.$serviceId);
    }
}
