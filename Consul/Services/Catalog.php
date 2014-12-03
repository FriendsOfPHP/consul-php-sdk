<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

class Catalog
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function register($node)
    {
        $params = array(
            'body' => (string) $node,
        );

        return $this->client->get('/v1/catalog/register', $params);
    }

    public function deregister($node)
    {
        $params = array(
            'body' => (string) $node,
        );

        return $this->client->get('/v1/catalog/deregister', $params);
    }

    public function datacenters()
    {
        return $this->client->get('/v1/catalog/datacenters');
    }

    public function nodes(array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/catalog/nodes', $params);
    }

    public function node($node, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/catalog/node/'.$node, $params);
    }

    public function services(array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/catalog/services', $params);
    }

    public function service($service, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc', 'tag')),
        );

        return $this->client->get('/v1/catalog/service/'.$service, $params);
    }
}
