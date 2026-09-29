<?php

namespace Consul\Tests\Services;

use Consul\Services\DiscoveryChain;
use PHPUnit\Framework\TestCase;

class DiscoveryChainTest extends TestCase
{
    private DiscoveryChain $discoveryChain;

    protected function setUp(): void
    {
        $this->discoveryChain = new DiscoveryChain();
    }

    public function testRead(): void
    {
        $chain = $this->discoveryChain->read('sdk-test-web', ['compile-dc' => 'dc1'])->json()['Chain'];

        self::assertSame('sdk-test-web', $chain['ServiceName']);
        self::assertSame('dc1', $chain['Datacenter']);
        self::assertSame('tcp', $chain['Protocol']);
    }

    public function testReadWithOverrides(): void
    {
        $chain = $this->discoveryChain->readWithOverrides('sdk-test-web', ['OverrideProtocol' => 'http'])->json()['Chain'];

        self::assertSame('sdk-test-web', $chain['ServiceName']);
        self::assertSame('http', $chain['Protocol']);
    }

    public function testReadDiscoveryChainWithEmptyOverrides(): void
    {
        $chain = $this->discoveryChain->readWithOverrides('sdk-test-web', [])->json()['Chain'];

        self::assertSame('tcp', $chain['Protocol']);
    }
}
