<?php

namespace Consul\Tests\Services;

use Consul\ConsulResponse;
use Consul\Exception\ClientException;
use Consul\Services\KV;
use PHPUnit\Framework\TestCase;

class KVTest extends TestCase
{
    private KV $kv;

    protected function setUp(): void
    {
        $this->kv = new KV();
        $this->kv->delete('test', ['recurse' => true]);
    }

    public function testSetGetWithDefaultOptions(): void
    {
        $value = date('r');
        $this->kv->put('test/my/key', $value);

        $response = $this->kv->get('test/my/key');
        $this->assertInstanceOf(ConsulResponse::class, $response);

        $json = $response->json();
        $this->assertSame($value, base64_decode($json[0]['Value']));
    }

    public function testSetGetWithRawOption(): void
    {
        $value = date('r');
        $this->kv->put('test/my/key', $value);

        $response = $this->kv->get('test/my/key', ['raw' => true]);
        $this->assertInstanceOf(ConsulResponse::class, $response);

        $body = (string) $response->getBody();
        $this->assertSame($value, $body);
    }

    public function testSetGetWithFlagsOption(): void
    {
        $flags = random_int(0, mt_getrandmax());
        $this->kv->put('test/my/key', 'hello', ['flags' => $flags]);

        $response = $this->kv->get('test/my/key');
        $this->assertInstanceOf(ConsulResponse::class, $response);

        $json = $response->json();
        $this->assertSame($flags, $json[0]['Flags']);
    }

    public function testSetGetWithKeysOption(): void
    {
        $this->kv->put('test/my/key1', 'hello 1');
        $this->kv->put('test/my/key2', 'hello 2');
        $this->kv->put('test/my/key3', 'hello 3');

        $response = $this->kv->get('test/my', ['keys' => true]);
        $this->assertInstanceOf(ConsulResponse::class, $response);

        $json = $response->json();
        $this->assertSame(['test/my/key1', 'test/my/key2', 'test/my/key3'], $json);
    }

    public function testDeleteWithDefaultOptions(): void
    {
        $this->kv->put('test/my/key', 'hello');
        $this->kv->get('test/my/key');
        $this->kv->delete('test/my/key');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches('/404/');

        $this->kv->get('test/my/key');
    }

    public function testGetWithIndexOption(): void
    {
        $this->kv->put('test/my/key', 'hello');

        $response = $this->kv->get('test/my/key');
        $index = $response->getHeaders()['x-consul-index'][0];

        $response = $this->kv->get('test/my/key', ['index' => $index, 'wait' => '10ms']);
        $this->assertSame($index, $response->getHeaders()['x-consul-index'][0]);
    }

    public function testDeleteWithCasOption(): void
    {
        $this->kv->put('test/my/key', 'hello');
        $modifyIndex = $this->kv->get('test/my/key')->json()[0]['ModifyIndex'];

        $this->assertSame('false', $this->kv->delete('test/my/key', ['cas' => $modifyIndex + 1000])->getBody());
        $this->assertSame('true', $this->kv->delete('test/my/key', ['cas' => $modifyIndex])->getBody());
    }

    public function testDeleteWithRecurseOption(): void
    {
        $this->kv->put('test/my/key1', 'hello 1');
        $this->kv->put('test/my/key2', 'hello 2');
        $this->kv->put('test/my/key3', 'hello 3');

        $this->kv->get('test/my/key1');
        $this->kv->get('test/my/key2');
        $this->kv->get('test/my/key3');

        $this->kv->delete('test/my', ['recurse' => true]);

        for ($i = 1; $i < 3; ++$i) {
            try {
                $this->kv->get('test/my/key'.$i);
                $this->fail('fail because the key does not exist anymore.');
            } catch (\Exception $e) {
                $this->assertInstanceOf(ClientException::class, $e);
                $this->assertStringContainsString('404', $e->getMessage());
            }
        }
    }
}
