<?php


namespace SensioLabs\Consul\Services;


interface HealthInterface
{
    const SERVICE_NAME = 'health';

    public function node($node, array $options = array());

    public function checks($service, array $options = array());

    public function service($service, array $options = array());

    public function state($state, array $options = array());
}
