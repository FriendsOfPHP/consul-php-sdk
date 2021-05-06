<?php


namespace SensioLabs\Consul\Services;


interface TXNInterface
{
    const SERVICE_NAME = 'txn';

    public function put(array $operations = array());
}
