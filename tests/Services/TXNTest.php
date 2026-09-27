<?php

namespace Consul\Tests\Services;

use Consul\Services\KV;
use Consul\Services\TXN;
use PHPUnit\Framework\TestCase;

class TXNTest extends TestCase
{
    private TXN $txn;

    protected function setUp(): void
    {
        $this->txn = new TXN();
        (new KV())->delete('test', ['recurse' => true]);
    }

    public function testPut(): void
    {
        $response = $this->txn->put([
            ['KV' => ['Verb' => 'set', 'Key' => 'test/txn/key1', 'Value' => base64_encode('hello 1')]],
            ['KV' => ['Verb' => 'set', 'Key' => 'test/txn/key2', 'Value' => base64_encode('hello 2')]],
        ]);

        self::assertTrue($response->isSuccessful());
        self::assertSame('hello 2', (new KV())->get('test/txn/key2', ['raw' => true])->getBody());
    }

    public function testPutWithNonListOperations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Operations Array!');

        $this->txn->put(['foo' => ['KV' => []]]);
    }

    public function testPutWithInvalidOperation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Operations!');

        $this->txn->put([['Foo' => []]]);
    }
}
