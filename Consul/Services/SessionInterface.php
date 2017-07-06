<?php


namespace SensioLabs\Consul\Services;


interface SessionInterface
{
    const SERVICE_NAME = 'session';

    public function create($body = null, array $options = array());

    public function destroy($sessionId, array $options = array());

    public function info($sessionId, array $options = array());

    public function node($node, array $options = array());

    public function all(array $options = array());

    public function renew($sessionId, array $options = array());
}
