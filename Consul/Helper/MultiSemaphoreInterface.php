<?php

namespace SensioLabs\Consul\Helper;

interface MultiSemaphoreInterface
{
    /**
     * @return Resource[]
     */
    public function getResources();

    /**
     * @return bool|mixed
     */
    public function acquire();

    /**
     * @return bool
     */
    public function renew();

    /**
     * @return void
     */
    public function release();
}
