<?php

namespace Consul;

class DsnResolver
{
    public static function resolve(array $options): string
    {
        $baseUrl = $_SERVER['CONSUL_HTTP_ADDR'] ?? $options['base_uri'] ?? 'http://127.0.0.1:8500';

        $scheme = parse_url($baseUrl, \PHP_URL_SCHEME);
        if (null === $scheme) {
            $baseUrl = 'http://'.$baseUrl;
        }

        return $baseUrl;
    }
}
