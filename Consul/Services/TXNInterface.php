<?php

namespace SensioLabs\Consul\Services;

interface TXNInterface
{
    public const SERVICE_NAME = 'txn';

    public function put(array $operations = []);
}
