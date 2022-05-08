<?php

namespace Consul\Helper;

use Consul\Services\KV;
use Consul\Services\Session;

class MultiLockHandler
{
    /** @var string[] */
    private array $resources;
    private int $ttl;
    private Session $session;
    private KV $kv;
    private string $sessionId;
    private string $lockPath;

    /**
     * @param array   $resources
     * @param int $ttl
     * @param Session $session
     * @param KV      $kv
     * @param string  $lockPath
     */
    public function __construct(array $resources, int $ttl, Session $session, KV $kv, $lockPath)
    {
        $this->resources = $resources;
        $this->ttl = $ttl;
        $this->session = $session;
        $this->kv = $kv;
        $this->lockPath = $lockPath;
    }

    /**
     * @return bool
     */
    public function lock(): bool
    {
        $result = true;

        // Start a session
        $this->sessionId = $this->session->create(['LockDelay' => 0, "TTL" => "{$this->ttl}s"])->json()['ID'];

        $lockedResources = [];

        foreach ($this->resources as $resource) {
            // Lock a key / value with the current session
            $lockAcquired = $this->kv->put($this->lockPath.$resource, '', ['acquire' => $this->sessionId])->json();

            if (false === $lockAcquired) {
                $result = false;
                break;
            } else {
                $lockedResources[] = $resource;
            }
        }

        if (!$result) {
            $this->releaseResources($lockedResources);
        }

        return $result;
    }

    /**
     * @return void
     */
    public function release(): void
    {
        $this->releaseResources($this->resources);
    }

    /**
     * @return bool
     */
    public function renew(): bool
    {
        return $this->session->renew($this->sessionId)->isSuccessful();
    }

    /**
     * @return string[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @param string[] $resources
     */
    private function releaseResources(array $resources): void
    {
        foreach ($resources as $resource) {
            $this->kv->delete($this->lockPath.$resource);
        }

        $this->session->destroy($this->sessionId);
    }
}