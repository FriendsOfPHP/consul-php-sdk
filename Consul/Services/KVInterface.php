<?php

namespace SensioLabs\Consul\Services;

interface KVInterface
{
    public const SERVICE_NAME = 'kv';

    public function get($key, array $options = []);

    public function put($key, $value, array $options = []);

    public function delete($key, array $options = []);
}
