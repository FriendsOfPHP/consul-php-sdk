<?php

namespace SensioLabs\Consul\Helper;

class MultiSemaphoreNull implements MultiSemaphoreInterface
{
    /**
     * @return Resource[]
     */
    public function getResources()
    {
        return [];
    }

    /**
     * @return bool|mixed
     */
    public function acquire()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function renew()
    {
        return true;
    }

    /**
     *
     */
    public function release()
    {
    }
}
