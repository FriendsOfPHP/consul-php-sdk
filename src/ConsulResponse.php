<?php

namespace Consul;

final readonly class ConsulResponse
{
    public function __construct(
        private array $headers,
        private string $body,
        private int $status = 200,
    ) {
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

    public function json(): mixed
    {
        return json_decode($this->body, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }
}
