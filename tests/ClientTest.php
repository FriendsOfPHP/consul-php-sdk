<?php

namespace Consul\Tests;

use Consul\Client;
use Consul\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private ?string $previousToken;

    protected function setUp(): void
    {
        $this->previousToken = $_SERVER['CONSUL_HTTP_TOKEN'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->previousToken) {
            unset($_SERVER['CONSUL_HTTP_TOKEN']);
        } else {
            $_SERVER['CONSUL_HTTP_TOKEN'] = $this->previousToken;
        }
    }

    public function testTokenFromEnvironment(): void
    {
        $_SERVER['CONSUL_HTTP_TOKEN'] = 'root';

        self::assertTrue((new Client())->get('/v1/acl/token/self')->isSuccessful());
    }

    public function testTokenFromOptionsWinsOverEnvironment(): void
    {
        $_SERVER['CONSUL_HTTP_TOKEN'] = 'root';

        $this->expectException(ClientException::class);

        (new Client(['headers' => ['X-Consul-Token' => 'invalid']]))->get('/v1/acl/token/self');
    }

    public function testWithoutToken(): void
    {
        unset($_SERVER['CONSUL_HTTP_TOKEN']);

        $this->expectException(ClientException::class);

        (new Client())->get('/v1/acl/token/self');
    }
}
