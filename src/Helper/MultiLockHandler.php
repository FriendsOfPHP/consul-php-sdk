<?php

namespace Consul\Helper;

use Consul\Services\KV;
use Consul\Services\Session;

class MultiLockHandler
{
    private string $sessionId;

    public function __construct(
        private readonly array $resources,
        private readonly int $ttl,
        private readonly Session $session,
        private readonly KV $kv,
        private readonly string $lockPath,
    ) {
    }

    public function lock(): bool
    {
        // Start a session
        $this->sessionId = $this->session->create(['LockDelay' => 0, 'TTL' => "{$this->ttl}s"])->json()['ID'];

        $lockedResources = [];

        foreach ($this->resources as $resource) {
            // Lock a key / value with the current session
            try {
                $lockAcquired = $this->kv->put($this->lockPath.$resource, '', ['acquire' => $this->sessionId])->json();
            } catch (\Exception) {
                $lockAcquired = false;
            }

            if (false === $lockAcquired) {
                $this->releaseResources($lockedResources);

                return false;
            }

            $lockedResources[] = $resource;
        }

        return true;
    }

    public function release(): void
    {
        $this->releaseResources($this->resources);
    }

    public function renew(): bool
    {
        return $this->session->renew($this->sessionId)->isSuccessful();
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    private function releaseResources(array $resources): void
    {
        foreach ($resources as $resource) {
            $this->kv->delete($this->lockPath.$resource);
        }

        $this->session->destroy($this->sessionId);
    }
}
