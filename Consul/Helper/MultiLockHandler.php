<?php

namespace SensioLabs\Consul\Helper;

use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;

class MultiLockHandler
{
    /** @var array */
    private $resources;

    /** @var int */
    private $ttl;

    /** @var Session */
    private $session;

    /** @var KV */
    private $kv;

    /** @var string */
    private $sessionId;

    /** @var string */
    private $lockPath;

    /**
     * @param array   $resources
     * @param int     $ttl
     * @param Session $session
     * @param KV      $kv
     * @param string  $lockPath
     */
    public function __construct(array $resources, $ttl, Session $session, KV $kv, $lockPath)
    {
        if (!is_int($ttl)) {
            throw new \Exception('Parameter ttl must be integer.');
        }

        $this->resources = $resources;
        $this->ttl = $ttl;
        $this->session = $session;
        $this->kv = $kv;
        $this->lockPath = $lockPath;
    }

    /**
     * @return bool
     */
    public function lock()
    {
        $result = true;

        // Start a session
        $session = $this->session->create('{"LockDelay":0, "TTL": "'.$this->ttl.'s"}')->json();
        $this->sessionId = $session['ID'];

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

    public function release()
    {
        $this->releaseResources($this->resources);
    }

    /**
     * @return bool
     */
    public function renew()
    {
        return false !== $this->session->renew($this->sessionId);
    }

    /**
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param array $resources
     */
    private function releaseResources(array $resources)
    {
        foreach ($resources as $resource) {
            $this->kv->delete($this->lockPath.$resource);
        }

        $this->session->destroy($this->sessionId);
    }
}
