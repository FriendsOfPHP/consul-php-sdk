<?php


namespace SensioLabs\Consul;


interface ClientInterface
{
    public function get($url = null, array $options = array());

    public function head($url, array $options = array());

    public function delete($url, array $options = array());

    public function put($url, array $options = array());

    public function patch($url, array $options = array());

    public function post($url, array $options = array());

    public function options($url, array $options = array());
}
