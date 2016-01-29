<?php

namespace SensioLabs\Consul\Helper;

use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;
use SensioLabs\Consul\Helper\MultiSemaphore\Resource;

class MultiSemaphoreFactory
{
    /** @var Session */
    private $session;

    /** @var KV */
    private $kv;

    /** @var string */
    private $keyPrefix;

    /**
     * @param Session $session
     * @param KV      $kv
     * @param string  $keyPrefix
     */
    public function __construct(Session $session, KV $kv, $keyPrefix)
    {
        $this->session = $session;
        $this->kv = $kv;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * @param Resource[] $resources
     * @param int        $ttl
     * @return MultiSemaphoreInterface
     */
    public function createMultiSemaphore(array $resources, $ttl = 60)
    {
        $result = null;

        if (!$resources) {
            $result = new MultiSemaphoreNull();
        } else {
            $result = new MultiSemaphore($resources, $ttl, $this->session, $this->kv, $this->keyPrefix);
        }

        return $result;
    }
}
