<?php

namespace SensioLabs\Consul;

interface ClientInterface
{
    public function get($url = null, array $options = []);

    public function head($url, array $options = []);

    public function delete($url, array $options = []);

    public function put($url, array $options = []);

    public function patch($url, array $options = []);

    public function post($url, array $options = []);

    public function options($url, array $options = []);
}
