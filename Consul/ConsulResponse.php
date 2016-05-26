<?php

namespace SensioLabs\Consul;

class ConsulResponse
{
    private $headers;
    private $body;

    public function __construct($headers, $body)
    {
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function json()
    {
        return json_decode($this->body, true);
    }
}
