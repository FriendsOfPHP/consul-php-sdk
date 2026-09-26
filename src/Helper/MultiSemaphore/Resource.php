<?php

namespace Consul\Helper\MultiSemaphore;

class Resource
{
    private int $acquired = 0;

    public function __construct(
        private readonly string $name,
        private readonly int $acquire,
        private readonly int $limit,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAcquire(): int
    {
        return $this->acquire;
    }

    public function getAcquired(): int
    {
        return $this->acquired;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setAcquired(int $acquired): void
    {
        $this->acquired = $acquired;
    }
}
