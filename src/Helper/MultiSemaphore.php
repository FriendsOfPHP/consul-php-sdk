<?php

namespace Consul\Helper;

use Consul\Helper\MultiSemaphore\Resource;
use Consul\Services\KV;
use Consul\Services\Session;

class MultiSemaphore
{
    private const META_DATA_KEY = '.semaphore';

    private readonly string $keyPrefix;
    private ?string $sessionId = null;

    public function __construct(
        private readonly array $resources,
        private readonly int $ttl,
        private readonly Session $session,
        private readonly KV $kv,
        string $keyPrefix,
    ) {
        $this->keyPrefix = trim($keyPrefix, '/');
    }

    public function getResources(): array
    {
        return $this->resources;
    }

    public function acquire(): bool
    {
        if (null !== $this->sessionId) {
            throw new \RuntimeException('Resources are acquired already');
        }

        // Start a session
        $this->sessionId = $this->session->create(['Name' => 'semaphore', 'LockDelay' => 0, 'TTL' => "{$this->ttl}s"])->json()['ID'];

        $result = false;

        try {
            $result = $this->acquireResources();
        } finally {
            if (!$result) {
                $this->release();
            }
        }

        return $result;
    }

    public function renew(): bool
    {
        return $this->session->renew($this->sessionId)->isSuccessful();
    }

    public function release(): void
    {
        if (null === $this->sessionId) {
            return;
        }

        foreach ($this->resources as $resource) {
            $this->kv->delete($this->getResourceKey($resource, $this->sessionId));
        }

        $this->session->destroy($this->sessionId);
        $this->sessionId = null;
    }

    private function acquireResources(): bool
    {
        foreach ($this->resources as $resource) {
            if (!$this->acquireResource($resource)) {
                return false;
            }
        }

        return true;
    }

    private function acquireResource(Resource $resource): bool
    {
        if (false === $this->kv->put($this->getResourceKey($resource, $this->sessionId), '', ['acquire' => $this->sessionId])->json()) {
            return false;
        }

        $metaDataKey = $this->getResourceKey($resource, self::META_DATA_KEY);

        // Fetch the current metadata
        $metaData = null;
        $items = $this->kv->get($this->getResourceKeyPrefix($resource), ['recurse' => true])->json();
        foreach ($items as $key => $item) {
            if ($item['Key'] === $metaDataKey) {
                $metaData = $item;
                $metaData['Value'] = json_decode(base64_decode($item['Value']), true);
                unset($items[$key]);

                break;
            }
        }

        // Keep only sessions still holding the resource, and clean up orphan keys
        $sessions = [];
        if (null !== $metaData) {
            foreach ($items as $item) {
                if (!isset($item['Session'])) {
                    $this->kv->delete($item['Key']);
                } elseif (isset($metaData['Value']['sessions'][$item['Session']])) {
                    $sessions[$item['Session']] = $metaData['Value']['sessions'][$item['Session']];
                }
            }
        }

        $resource->setAcquired(min($resource->getAcquire(), $resource->getLimit() - array_sum($sessions)));

        if ($resource->getAcquired() <= 0) {
            return false;
        }

        // Register the current session in the metadata and save it
        $sessions[$this->sessionId] = $resource->getAcquired();

        return true === $this->kv->put(
            $metaDataKey,
            ['limit' => $resource->getLimit(), 'sessions' => $sessions],
            ['cas' => $metaData['ModifyIndex'] ?? 0],
        )->json();
    }

    private function getResourceKeyPrefix(Resource $resource): string
    {
        return $this->keyPrefix.'/'.$resource->getName();
    }

    private function getResourceKey(Resource $resource, string $name): string
    {
        return $this->getResourceKeyPrefix($resource).'/'.$name;
    }
}
