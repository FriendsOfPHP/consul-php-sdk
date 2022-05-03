<?php

namespace SensioLabs\Consul\Services;

interface SessionInterface
{
    public const SERVICE_NAME = 'session';

    public function create($body = null, array $options = []);

    public function destroy($sessionId, array $options = []);

    public function info($sessionId, array $options = []);

    public function node($node, array $options = []);

    public function all(array $options = []);

    public function renew($sessionId, array $options = []);
}
