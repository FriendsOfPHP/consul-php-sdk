<?php

namespace Consul;

final class ConsulResponse
{
    private const HTTP_OK = 200;
    private const HTTP_CREATED = 201;
    private const HTTP_NO_CONTENT = 204;

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

    public function json()
    {
        return json_decode($this->body, true, 512, \JSON_THROW_ON_ERROR);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::HTTP_OK, self::HTTP_CREATED, self::HTTP_NO_CONTENT], true);
    }
}
