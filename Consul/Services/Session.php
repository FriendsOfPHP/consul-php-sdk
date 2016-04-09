<?php

namespace SensioLabs\Consul\Services;

use SensioLabs\Consul\Client;
use SensioLabs\Consul\OptionsResolver;

class Session
{
    private $client;

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client();
    }

    public function create($body = null, array $options = array())
    {
        $params = array(
            'body' => $body,
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->put('/v1/session/create', $params);
    }

    public function destroy($sessionId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->put('/v1/session/destroy/'.$sessionId, $params);
    }

    public function info($sessionId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/session/info/'.$sessionId, $params);
    }

    public function node($node, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/session/node/'.$node, $params);
    }

    public function all(array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->get('/v1/session/list', $params);
    }

    public function renew($sessionId, array $options = array())
    {
        $params = array(
            'query' => OptionsResolver::resolve($options, array('dc')),
        );

        return $this->client->put('/v1/session/renew/'.$sessionId, $params);
    }
}
