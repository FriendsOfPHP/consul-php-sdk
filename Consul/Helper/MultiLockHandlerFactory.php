<?php

namespace SensioLabs\Consul\Helper;

use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;

class MultiLockHandlerFactory
{
    /** @var Session */
    private $session;

    /** @var KV */
    private $kv;

    /** @var string */
    private $lockPath;

    /**
     * @param Session $session
     * @param KV      $kv
     * @param string  $lockPath
     */
    public function __construct(Session $session, KV $kv, $lockPath)
    {
        $this->session = $session;
        $this->kv = $kv;
        $this->lockPath = $lockPath;
    }

    /**
     * @param array $resources
     * @param int   $ttl
     * @return MultiLockHandler
     */
    public function createMultiLockHandler(array $resources, $ttl = 60)
    {
        return new MultiLockHandler($resources, $ttl, $this->session, $this->kv, $this->lockPath);
    }
}
