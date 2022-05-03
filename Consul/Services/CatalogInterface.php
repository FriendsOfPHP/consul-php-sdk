<?php

namespace SensioLabs\Consul\Services;

interface CatalogInterface
{
    public const SERVICE_NAME = 'catalog';

    public function register($node);

    public function deregister($node);

    public function datacenters();

    public function nodes(array $options = []);

    public function node($node, array $options = []);

    public function services(array $options = []);

    public function service($service, array $options = []);
}
