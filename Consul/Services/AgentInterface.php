<?php

namespace SensioLabs\Consul\Services;

interface AgentInterface
{
    public const SERVICE_NAME = 'agent';

    public function checks();

    public function services();

    public function members(array $options = []);

    public function self();

    public function join($address, array $options = []);

    public function forceLeave($node);

    public function registerCheck($check);

    public function deregisterCheck($checkId);

    public function passCheck($checkId, array $options = []);

    public function warnCheck($checkId, array $options = []);

    public function failCheck($checkId, array $options = []);

    public function registerService($service);

    public function deregisterService($serviceId);
}
