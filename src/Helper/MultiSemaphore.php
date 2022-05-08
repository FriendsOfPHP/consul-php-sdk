<?php

namespace Consul\Helper;

use Consul\Services\KV;
use Consul\Services\Session;
use Consul\Helper\MultiSemaphore\Resource;
use RuntimeException;

class MultiSemaphore
{
    private Session $session;
    private KV $kv;
    private ?string $sessionId = null;
    /** @var Resource[] */
    private array $resources;
    private string $keyPrefix;
    private int $ttl;
    private string $metaDataKey = '.semaphore';

    /**
     * @param Resource[] $resources
     * @param int $ttl
     * @param Session    $session
     * @param KV         $kv
     * @param string $keyPrefix
     */
    public function __construct(array $resources, int $ttl, Session $session, KV $kv, string $keyPrefix)
    {
        $this->resources = $resources;
        $this->ttl = $ttl;
        $this->session = $session;
        $this->kv = $kv;
        $this->keyPrefix = trim($keyPrefix, '/');
    }

    /**
     * @return Resource[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @return bool
     */
    public function acquire(): bool
    {
        if (null !== $this->sessionId) {
            throw new RuntimeException('Resources are acquired.');
        }

        $result = true;

        // Start a session
        $session = $this->session->create(["Name" => "semaphore", "LockDelay" => 0, "TTL" => "{$this->ttl}s"])->json();
        $this->sessionId = $session['ID'];

        foreach ($this->resources as $resource) {
            if (false === $this->kv->put($this->getResourceKey($resource, $this->sessionId), '', ['acquire' => $this->sessionId])->json()) {
                $result = false;
            } else {
                $semaphoreMetaDataValue = [
                    'limit' => $resource->getLimit(),
                    'sessions' => [],
                ];

                // get actual metadata
                $semaphoreDataItems = $this->kv->get($this->getResourceKeyPrefix($resource), ['recurse' => true])->json();
                foreach ($semaphoreDataItems as $key => $item) {
                    if ($item['Key'] == $this->getResourceKey($resource, $this->metaDataKey)) {
                        $semaphoreMetaDataActual = $item;
                        $semaphoreMetaDataActual['Value'] = json_decode(base64_decode($semaphoreMetaDataActual['Value']), true);
                        unset($semaphoreDataItems[$key]);
                        break;
                    }
                }

                // build new metadata
                if (isset($semaphoreMetaDataActual)) {
                    foreach ($semaphoreDataItems as $item) {
                        if (isset($item['Session'])) {
                            if (isset($semaphoreMetaDataActual['Value']['sessions'][$item['Session']])) {
                                $semaphoreMetaDataValue['sessions'][$item['Session']] = $semaphoreMetaDataActual['Value']['sessions'][$item['Session']];
                            }
                        } else {
                            $this->kv->delete($item['Key']);
                        }
                    }
                }

                $resource->setAcquired(
                    min($resource->getAcquire(), ($semaphoreMetaDataValue['limit'] - array_sum($semaphoreMetaDataValue['sessions'])))
                );

                // add new element to metadata and save it
                if ($resource->getAcquired() > 0) {
                    $semaphoreMetaDataValue['sessions'][$this->sessionId] = $resource->getAcquired();
                    $result = $this->kv->put(
                        $this->getResourceKey($resource, $this->metaDataKey),
                        $semaphoreMetaDataValue,
                        ['cas' => isset($semaphoreMetaDataActual) ? $semaphoreMetaDataActual['ModifyIndex'] : 0]
                    )->json();
                } else {
                    $result = false;
                }
            }

            if (!$result) {
                break;
            }
        }

        if (!$result) {
            $this->release();
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function renew(): bool
    {
        return $this->session->renew($this->sessionId)->isSuccessful();
    }

    /**
     * Release resources if they were acquired
     */
    public function release(): void
    {
        if ($this->sessionId) {
            foreach ($this->resources as $resource) {
                $this->kv->delete($this->getResourceKey($resource, $this->sessionId));
            }

            $this->session->destroy($this->sessionId);
            $this->sessionId = null;
        }
    }

    /**
     * @param Resource $resource
     *
     * @return string
     */
    private function getResourceKeyPrefix(Resource $resource): string
    {
        return $this->keyPrefix.'/'.$resource->getName();
    }

    /**
     * @param Resource $resource
     * @param string $name
     *
     * @return string
     */
    private function getResourceKey(Resource $resource, string $name): string
    {
        return $this->getResourceKeyPrefix($resource).'/'.$name;
    }
}