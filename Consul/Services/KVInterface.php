<?php


namespace SensioLabs\Consul\Services;


interface KVInterface
{
    const SERVICE_NAME = 'kv';

    public function get($key, array $options = array());
    public function put($key, $value, array $options = array());
    public function delete($key, array $options = array());
}
