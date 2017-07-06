<?php


namespace SensioLabs\Consul\Services;


interface CatalogInterface
{
    const SERVICE_NAME = 'catalog';

    public function register($node);

    public function deregister($node);

    public function datacenters();

    public function nodes(array $options = array());

    public function node($node, array $options = array());

    public function services(array $options = array());

    public function service($service, array $options = array());
}
