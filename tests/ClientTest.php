<?php

namespace Consul\Tests;

use Consul\Client;
use Consul\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

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

    public function testMultiValuedQueryParametersAreRepeated(): void
    {
        $url = null;
        $client = new Client(client: new MockHttpClient(static function (string $method, string $requestUrl) use (&$url) {
            $url = $requestUrl;

            return new MockResponse('[]');
        }, 'http://127.0.0.1:8500'));

        $client->get('/v1/health/service/api', ['query' => [
            'dc' => 'dc1',
            'tag' => ['a', 'b c'],
            'node-meta' => ['rack:r1', 'zone:z1'],
        ]]);

        self::assertSame('http://127.0.0.1:8500/v1/health/service/api?tag=a&tag=b%20c&node-meta=rack%3Ar1&node-meta=zone%3Az1&dc=dc1', $url);
    }
}
