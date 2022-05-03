<?php

namespace SensioLabs\Consul;

final class ConsulResponse
{
    private array $headers;
    private string $body;
    private int $status;

    public function __construct(array $headers, string $body, int $status = 200)
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->status = $status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function json(): ?array
    {
        return json_decode($this->body, true, 512, \JSON_THROW_ON_ERROR);
    }
}
