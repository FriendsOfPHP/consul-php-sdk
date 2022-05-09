<?php

namespace Consul\Helper\MultiSemaphore;

class Resource
{
    private string $name;
    private int $acquire;
    private int $acquired;
    private int $limit;

    public function __construct(string $name, int $acquire, int $limit)
    {
        $this->name = $name;
        $this->acquire = $acquire;
        $this->acquired = 0;
        $this->limit = $limit;
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
