<?php

namespace Consul;

interface ClientInterface
{
    public function get(string $url = null, array $options = []): ConsulResponse;

    public function head(string $url, array $options = []): ConsulResponse;

    public function delete(string $url, array $options = []): ConsulResponse;

    public function put(string $url, array $options = []): ConsulResponse;

    public function patch(string $url, array $options = []): ConsulResponse;

    public function post(string $url, array $options = []): ConsulResponse;

    public function options(string $url, array $options = []): ConsulResponse;
}
