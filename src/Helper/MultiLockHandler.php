<?php

namespace Consul\Helper;

use Consul\Services\KV;
use Consul\Services\Session;

class MultiLockHandler
{
    private array $resources;
    private int $ttl;
    private Session $session;
    private KV $kv;
    private string $sessionId;
    private string $lockPath;

    public function __construct(array $resources, int $ttl, Session $session, KV $kv, string $lockPath)
    {
        $this->resources = $resources;
        $this->ttl = $ttl;
        $this->session = $session;
        $this->kv = $kv;
        $this->lockPath = $lockPath;
    }

    public function lock(): bool
    {
        // Start a session
        $this->sessionId = $this->session->create(['LockDelay' => 0, 'TTL' => "{$this->ttl}s"])->json()['ID'];

        $result = true;
        $lockedResources = [];

        try {
            foreach ($this->resources as $resource) {
                // Lock a key / value with the current session
                $lockAcquired = $this->kv->put($this->lockPath.$resource, '', ['acquire' => $this->sessionId])->json();

                if (false === $lockAcquired) {
                    $result = false;

                    break;
                }

                $lockedResources[] = $resource;
            }
        } catch (\Exception $e) {
            $result = false;
        } finally {
            if (!$result) {
                $this->releaseResources($lockedResources);
            }
        }

        return $result;
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
