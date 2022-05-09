<?php

namespace Consul\Helper\MultiSemaphore;

class Resource
{
    private string $name;
    private int $acquire;
    private int $acquired;
    private int $limit;

    /**
     * @param string $name
     * @param int $acquire
     * @param int $limit
     */
    public function __construct(string $name, int $acquire, int $limit)
    {
        $this->name = $name;
        $this->acquire = $acquire;
        $this->acquired = 0;
        $this->limit = $limit;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getAcquire(): int
    {
        return $this->acquire;
    }

    /**
     * @return int
     */
    public function getAcquired(): int
    {
        return $this->acquired;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $acquired
     */
    public function setAcquired(int $acquired): void
    {
        $this->acquired = $acquired;
    }
}
