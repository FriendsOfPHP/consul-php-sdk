<?php

namespace Consul\Tests\Helper;

use Consul\Services\KV;
use Consul\Services\Session;
use Consul\Helper\MultiLockHandler;
use PHPUnit\Framework\TestCase;
use Consul\Exception\ClientException;

class MultiLockHandlerTest extends TestCase
{
    public function testLockTheSameResource(): void
    {
        $resources = ['resource1', 'resource2'];

        $multiLockHandler1 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler2 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');

        self::assertTrue($multiLockHandler1->lock());
        self::assertFalse($multiLockHandler2->lock());

        $multiLockHandler1->release();
    }

    public function testLockDifferentSameResource(): void
    {
        $multiLockHandler1 = new MultiLockHandler(['resource1', 'resource2', 'resource3'], 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler2 = new MultiLockHandler(['resource4', 'resource5', 'resource6'], 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler3 = new MultiLockHandler(['resource7', 'resource8'], 10, new Session(), new KV(), 'test/lock/');

        self::assertTrue($multiLockHandler1->lock());
        self::assertTrue($multiLockHandler2->lock());
        self::assertTrue($multiLockHandler3->lock());

        $multiLockHandler1->release();
        $multiLockHandler2->release();
        $multiLockHandler3->release();
    }

    public function testRenew(): void
    {
        $resources = ['resource1', 'resource2'];

        $multiLockHandler1 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler2 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler3 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler4 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');

        self::assertTrue($multiLockHandler1->lock());
        self::assertFalse($multiLockHandler2->lock());
        self::assertFalse($multiLockHandler3->lock());
        self::assertFalse($multiLockHandler4->lock());

        sleep(8);

        self::assertTrue($multiLockHandler1->renew());

        sleep(8);

        self::assertFalse($multiLockHandler2->lock());
        self::assertFalse($multiLockHandler3->lock());
        self::assertFalse($multiLockHandler4->lock());

        sleep(15);

        self::assertTrue($multiLockHandler2->lock());
        self::assertFalse($multiLockHandler3->lock());
        self::assertFalse($multiLockHandler4->lock());

        $multiLockHandler1->release();
        $multiLockHandler2->release();
    }

    public function testRenewExpiredSession(): void
    {
        $this->expectException(ClientException::class);

        $resources = ['resource1', 'resource2'];
        $multiLockHandler = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        self::assertTrue($multiLockHandler->lock());
        sleep(21);
        $multiLockHandler->renew();
    }

    public function testRelease(): void
    {
        $resources = ['resource1', 'resource2'];

        $multiLockHandler1 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');
        $multiLockHandler2 = new MultiLockHandler($resources, 10, new Session(), new KV(), 'test/lock/');

        self::assertTrue($multiLockHandler1->lock());
        self::assertFalse($multiLockHandler2->lock());

        $multiLockHandler1->release();

        self::assertTrue($multiLockHandler2->lock());

        $multiLockHandler2->release();
    }
}
