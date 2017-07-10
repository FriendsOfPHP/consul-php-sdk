<?php

namespace SensioLabs\Consul;

final class ConsulResponse
{
    private $headers;
    private $body;
    private $status;

    public function __construct($headers, $body, $status = 200)
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->status = $status;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getStatusCode()
    {
        return $this->status;
    }

    public function json()
    {
        return json_decode($this->body, true);
    }
}
