<?php


namespace SensioLabs\Consul\Services;


interface AgentInterface
{
    const SERVICE_NAME = 'agent';

    public function checks();

    public function services();

    public function members(array $options = array());

    public function self();

    public function join($address, array $options = array());

    public function forceLeave($node);

    public function registerCheck($check);

    public function deregisterCheck($checkId);

    public function passCheck($checkId, array $options = array());

    public function warnCheck($checkId, array $options = array());

    public function failCheck($checkId, array $options = array());

    public function registerService($service);

    public function deregisterService($serviceId);
}
