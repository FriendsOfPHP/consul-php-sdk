<?php

namespace SensioLabs\Consul\Helper;

/**
 * Class MultiSemaphoreInterface
 * @package ETWater\ESP\ConsulBundle\Service
 */
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
