<?php

namespace Consul\Tests\Helper;

use Consul\Helper\MultiSemaphore;
use Consul\Helper\MultiSemaphore\Resource;
use Consul\Services\KV;
use Consul\Services\Session;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultiSemaphoreTest extends TestCase
{
    public function testGeneral(): void
    {
        $resources = [
            new Resource('resource1', 2, 7),
            new Resource('resource2', 3, 6),
            new Resource('resource3', 1, 1),
        ];

        $semaphore1 = new MultiSemaphore($resources, 60, new Session(), new KV(), 'test/semaphore');
        static::assertTrue($semaphore1->acquire());

        $semaphore2 = new MultiSemaphore($resources, 60, new Session(), new KV(), 'test/semaphore');
        static::assertFalse($semaphore2->acquire());

        $semaphore3 = new MultiSemaphore($resources, 60, new Session(), new KV(), 'test/semaphore');
        static::assertFalse($semaphore3->acquire());

        $semaphore1->release();
        static::assertTrue($semaphore3->acquire());

        $resources = [
            new Resource('resource1', 2, 7),
            new Resource('resource2', 3, 6),
        ];

        $semaphore4 = new MultiSemaphore($resources, 60, new Session(), new KV(), 'test/semaphore');
        static::assertTrue($semaphore4->acquire());

        $semaphore5 = new MultiSemaphore($resources, 60, new Session(), new KV(), 'test/semaphore');
        static::assertFalse($semaphore5->acquire());

        $semaphore3->release();
        $semaphore4->release();
    }

    public function testTimeout(): void
    {
        $resources = [
            new Resource('resource1', 7, 7),
            new Resource('resource2', 6, 6),
        ];

        $semaphore1 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');
        static::assertTrue($semaphore1->acquire());

        $semaphore2 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');
        static::assertFalse($semaphore2->acquire());

        sleep(45);

        static::assertTrue($semaphore2->acquire());

        $semaphore1->release();
        $semaphore2->release();
    }

    public function testRenew(): void
    {
        $resources = [
            new Resource('resource1', 7, 7),
            new Resource('resource2', 2, 6),
        ];

        $semaphore1 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');
        $semaphore2 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');

        static::assertTrue($semaphore1->acquire());
        static::assertFalse($semaphore2->acquire());

        for ($i = 0; $i < 4; ++$i) {
            sleep(15);
            $semaphore1->renew();
        }

        static::assertFalse($semaphore2->acquire());

        $semaphore1->release();
    }

    public function testExceptionAcquireAcquired(): void
    {
        $this->expectExceptionObject(new RuntimeException('Resources are acquired already'));

        $resources = [
            new Resource('resource11', 7, 7),
        ];

        $semaphore1 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');
        $semaphore1->acquire();
        $semaphore1->acquire();
    }

    public function testReleaseNotAcquired(): void
    {
        $resources = [
            new Resource('resource12', 7, 7),
        ];

        $semaphore1 = new MultiSemaphore($resources, 15, new Session(), new KV(), 'test/semaphore');
        $semaphore1->release();
        $semaphore1->release();

        static::assertTrue(true);
    }
}
