<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

class Health
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function node($node, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/health/node/'.$node, $params);
    }

    public function checks($service, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/health/checks/'.$service, $params);
    }

    public function service($service, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc', 'tag', 'passing')),
        );

        return $this->client->get('/v1/health/service/'.$service, $params);
    }

    public function state($state, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/health/state/'.$state, $params);
    }
}
