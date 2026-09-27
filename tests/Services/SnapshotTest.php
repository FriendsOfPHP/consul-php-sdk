<?php

namespace Consul\Tests\Services;

use Consul\Services\KV;
use Consul\Services\Snapshot;
use PHPUnit\Framework\TestCase;

class SnapshotTest extends TestCase
{
    private Snapshot $snapshot;
    private KV $kv;
    private string $key;

    protected function setUp(): void
    {
        $this->snapshot = new Snapshot();
        $this->kv = new KV();
        $this->key = 'test/snapshot/'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->kv->delete($this->key);
    }

    public function testSave(): void
    {
        $response = $this->snapshot->save(['dc' => 'dc1', 'stale' => true]);

        self::assertTrue($response->isSuccessful());
        // The snapshot is a gzipped tarball
        self::assertStringStartsWith("\x1f\x8b", $response->getBody());
        self::assertArrayHasKey('x-consul-index', $response->getHeaders());
    }

    public function testRestore(): void
    {
        $this->kv->put($this->key, 'before');
        $snapshot = $this->snapshot->save()->getBody();
        $this->kv->put($this->key, 'after');

        $response = $this->snapshot->restore($snapshot, ['dc' => 'dc1']);

        self::assertTrue($response->isSuccessful());
        self::assertSame('before', $this->kv->get($this->key, ['raw' => true])->getBody());
    }
}
