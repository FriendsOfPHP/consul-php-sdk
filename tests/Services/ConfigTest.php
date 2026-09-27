<?php

namespace Consul\Tests\Services;

use Consul\Exception\ClientException;
use Consul\Services\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    protected function tearDown(): void
    {
        $this->config->delete('service-defaults', 'sdk-test-config');
    }

    public function testApply(): void
    {
        $response = $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'http']);

        self::assertTrue($response->json());
    }

    public function testApplyConfigWithCas(): void
    {
        $response = $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'http'], ['cas' => 0]);
        self::assertTrue($response->json());

        $response = $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'grpc'], ['cas' => 0]);
        self::assertFalse($response->json());
    }

    public function testRead(): void
    {
        $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'http']);

        $entry = $this->config->read('service-defaults', 'sdk-test-config')->json();

        self::assertSame('service-defaults', $entry['Kind']);
        self::assertSame('sdk-test-config', $entry['Name']);
        self::assertSame('http', $entry['Protocol']);
    }

    public function testList(): void
    {
        $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'http']);

        $entries = $this->config->list('service-defaults', ['filter' => 'Name == "sdk-test-config"'])->json();

        self::assertCount(1, $entries);
        self::assertSame('sdk-test-config', $entries[0]['Name']);
    }

    public function testDelete(): void
    {
        $this->config->apply(['Kind' => 'service-defaults', 'Name' => 'sdk-test-config', 'Protocol' => 'http']);

        $response = $this->config->delete('service-defaults', 'sdk-test-config');
        self::assertTrue($response->isSuccessful());

        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches('/404/');

        $this->config->read('service-defaults', 'sdk-test-config');
    }
}
