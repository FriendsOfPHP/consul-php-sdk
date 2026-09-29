<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Services\Config;
use Consul\Services\Peering;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class PeeringTest extends TestCase
{
    private Peering $peering;

    protected function setUp(): void
    {
        $this->peering = new Peering();
    }

    protected function tearDown(): void
    {
        $this->peering->delete('sdk-test-peer');
    }

    public function testGenerateToken(): void
    {
        $response = $this->peering->generateToken(['PeerName' => 'sdk-test-peer', 'Meta' => ['env' => 'test']]);

        $token = json_decode(base64_decode($response->json()['PeeringToken']), true);
        self::assertArrayHasKey('ServerAddresses', $token);
    }

    public function testEstablish(): void
    {
        // Establishing a peering requires a second cluster
        $client = new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('http://127.0.0.1:8500/v1/peering/establish', $url);
            self::assertSame('{"PeerName":"sdk-test-peer","PeeringToken":"token"}', $options['body']);

            return new MockResponse('{}');
        }, 'http://127.0.0.1:8500');

        $response = (new Peering(new Client(client: $client)))->establish(['PeerName' => 'sdk-test-peer', 'PeeringToken' => 'token']);

        self::assertTrue($response->isSuccessful());
    }

    public function testRead(): void
    {
        $this->peering->generateToken(['PeerName' => 'sdk-test-peer', 'Meta' => ['env' => 'test']]);

        $peering = $this->peering->read('sdk-test-peer', ['consistent' => true])->json();

        self::assertSame('sdk-test-peer', $peering['Name']);
        self::assertSame('PENDING', $peering['State']);
        self::assertSame(['env' => 'test'], $peering['Meta']);
    }

    public function testDelete(): void
    {
        $this->peering->generateToken(['PeerName' => 'sdk-test-peer']);

        $response = $this->peering->delete('sdk-test-peer');

        self::assertTrue($response->isSuccessful());
    }

    public function testList(): void
    {
        $this->peering->generateToken(['PeerName' => 'sdk-test-peer']);

        $names = array_column($this->peering->list()->json(), 'Name');

        self::assertContains('sdk-test-peer', $names);
    }

    public function testListExportedServices(): void
    {
        $this->peering->generateToken(['PeerName' => 'sdk-test-peer']);
        $config = new Config();
        $config->apply(['Kind' => 'exported-services', 'Name' => 'default', 'Services' => [['Name' => 'sdk-test-web', 'Consumers' => [['Peer' => 'sdk-test-peer']]]]]);

        try {
            $services = $this->peering->listExportedServices()->json();
        } finally {
            $config->delete('exported-services', 'default');
        }

        self::assertSame([['Service' => 'sdk-test-web', 'Consumers' => ['Peers' => ['sdk-test-peer']]]], $services);
    }

    public function testListImportedServices(): void
    {
        // Importing services requires an established peering with a second cluster
        $response = $this->peering->listImportedServices();

        self::assertSame([], $response->json());
    }
}
