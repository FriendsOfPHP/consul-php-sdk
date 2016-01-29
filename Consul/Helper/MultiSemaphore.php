<?php

namespace SensioLabs\Consul\Helper;

use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;
use SensioLabs\Consul\Helper\MultiSemaphore\Resource;

class MultiSemaphore implements MultiSemaphoreInterface
{
    /** @var Session */
    private $session;

    /** @var KV */
    private $kv;

    /** @var string */
    private $sessionId;

    /** @var array */
    private $resources;

    /** @var string */
    private $keyPrefix;

    /** @var integer */
    private $ttl;

    /** @var string */
    private $metaDataKey = '.semaphore';

    /**
     * @param Resource[] $resources
     * @param int        $ttl
     * @param Session    $session
     * @param KV         $kv
     * @param string     $keyPrefix
     */
    public function __construct(array $resources, $ttl, Session $session, KV $kv, $keyPrefix)
    {
        if (!is_int($ttl)) {
            throw new \Exception('Parameter ttl must be integer.');
        }

        $this->resources = $resources;
        $this->ttl = $ttl;
        $this->session = $session;
        $this->kv = $kv;
        $this->keyPrefix = trim($keyPrefix, '/');
    }

    /**
     * @return Resource[]
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @return bool|mixed
     */
    public function acquire()
    {
        if ($this->sessionId) {
            throw new \Exception('Resources are acquired.');
        }

        $result = true;

        // Start a session
        $session = $this->session->create('{"Name": "semaphor", "LockDelay":0, "TTL": "'.$this->ttl.'s"}')->json();
        $this->sessionId = $session['ID'];

        $lockedResources = [];
        foreach ($this->resources as $resource) {
            if (false === $this->kv->put($this->getResourceKey($resource, $this->sessionId), '', ['acquire' => $this->sessionId])->json()) {
                $result = false;
            } else {
                $lockedResources[] = $resource;

                $semaphoreMetaDataValue = [
                    'limit' => $resource->limit,
                    'sessions' => [],
                ];

                // get actuall metadata
                $semaphorDataItems = $this->kv->get($this->getResourceKeyPrefix($resource), ['recurse' => true])->json();
                foreach ($semaphorDataItems as $key => $item) {
                    if ($item['Key'] == $this->getResourceKey($resource, $this->metaDataKey)) {
                        $semaphoreMetaDataActual = $item;
                        $semaphoreMetaDataActual['Value'] = json_decode(base64_decode($semaphoreMetaDataActual['Value']), true);
                        unset($semaphorDataItems[$key]);
                        break;
                    }
                }

                // build new metadata
                if (isset($semaphoreMetaDataActual)) {
                    foreach ($semaphorDataItems as $item) {
                        if (isset($item['Session'])) {
                            if (isset($semaphoreMetaDataActual['Value']['sessions'][$item['Session']])) {
                                $semaphoreMetaDataValue['sessions'][$item['Session']] = $semaphoreMetaDataActual['Value']['sessions'][$item['Session']];
                            }
                        } else {
                            $this->kv->delete($item['Key']);
                        }
                    }
                }

                $resource->acquired = min($resource->acquire, ($semaphoreMetaDataValue['limit'] - array_sum($semaphoreMetaDataValue['sessions'])));

                // add new elemet to metadata and save it
                if ($resource->acquired) {
                    $semaphoreMetaDataValue['sessions'][$this->sessionId] = $resource->acquired;
                    $result = $this->kv->put(
                        $this->getResourceKey($resource, $this->metaDataKey),
                        json_encode($semaphoreMetaDataValue),
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
    public function renew()
    {
        return false !== $this->session->renew($this->sessionId);
    }

    /**
     * Release resources if they were aquired
     */
    public function release()
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
     * @return string
     */
    private function getResourceKeyPrefix(Resource $resource)
    {
        return $this->keyPrefix.'/'.$resource->name;
    }

    /**
     * @param Resource $resource
     * @param string   $name
     * @return string
     */
    private function getResourceKey(Resource $resource, $name)
    {
        return $this->getResourceKeyPrefix($resource).'/'.$name;
    }
}
