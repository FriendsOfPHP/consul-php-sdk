<?php

namespace SensioLabs\Consul\Helper\MultiSemaphore;

class Resource
{
    /** @var string */
    public $name;

    /** @var int */
    public $acquire = 0;

    /** @var int */
    public $acquired = 0;

    /** @var int */
    public $limit = 0;

    /**
     * @param string $name
     * @param int    $acquire
     * @param int    $limit
     */
    public function __construct($name, $acquire, $limit)
    {
        $this->name = $name;
        $this->acquire = $acquire;
        $this->limit = $limit;
    }
}
