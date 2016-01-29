<?php

namespace SensioLabs\Consul\Tests\Integration\Helper;

use SensioLabs\Consul\Helper\MultiSemaphore\Resource;
use SensioLabs\Consul\Helper\MultiSemaphoreFactory;
use SensioLabs\Consul\Helper\MultiSemaphore;
use SensioLabs\Consul\ServiceFactory;

class MultiSemaphoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultiSemaphoreFactory
     */
    private $multiSemaphoreFactory;

    /**
     *
     */
    public function setUp()
    {
        $serviceFactory = new ServiceFactory(['base_url' => 'http://127.0.0.1:8500']);

        $this->multiSemaphoreFactory = new MultiSemaphoreFactory(
            $serviceFactory->get('session'),
            $serviceFactory->get('kv'),
            'test/semaphore'
        );
    }

    /**
     *
     */
    public function testGeneral()
    {
        $resources = [
            new Resource('resource1', 2, 7),
            new Resource('resource2', 3, 6),
            new Resource('resource3', 1, 1),
        ];

        $semaphore1 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 60);
        static::assertTrue($semaphore1->acquire());

        $semaphore2 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 60);
        static::assertFalse($semaphore2->acquire());

        $semaphore3 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 60);
        static::assertFalse($semaphore3->acquire());

        $semaphore1->release();
        static::assertTrue($semaphore3->acquire());

        $resources = [
            new Resource('resource1', 2, 7),
            new Resource('resource2', 3, 6),
        ];

        $semaphore4 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 60);
        static::assertTrue($semaphore4->acquire());

        $semaphore5 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 60);
        static::assertFalse($semaphore5->acquire());

        $semaphore3->release();
        $semaphore4->release();
    }

    /**
     * @throws \Exception
     */
    public function testTimeout()
    {
        $resources = [
            new Resource('resource1', 7, 7),
            new Resource('resource2', 6, 6),
        ];

        $semaphore1 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);
        static::assertTrue($semaphore1->acquire());

        $semaphore2 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);
        static::assertFalse($semaphore2->acquire());

        sleep(45);

        static::assertTrue($semaphore2->acquire());

        $semaphore1->release();
        $semaphore2->release();
    }

    /**
     * @throws \Exception
     */
    public function testRenew()
    {
        $resources = [
            new Resource('resource1', 7, 7),
            new Resource('resource2', 2, 6),
        ];

        $semaphore1 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);
        $semaphore2 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);

        static::assertTrue($semaphore1->acquire());
        static::assertFalse($semaphore2->acquire());

        for ($i = 0; $i < 4; $i++) {
            sleep(15);
            $semaphore1->renew();
        }

        static::assertFalse($semaphore2->acquire());

        $semaphore1->release();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Resources are acquired.
     */
    public function testExceptionAcquireAcquired()
    {
        $resources = [
            new Resource('resource11', 7, 7),
        ];

        $semaphore1 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);
        $semaphore1->acquire();
        $semaphore1->acquire();
    }

    /**
     *
     */
    public function testReleaseNotAcquired()
    {
        $resources = [
            new Resource('resource12', 7, 7),
        ];

        $semaphore1 = $this->multiSemaphoreFactory->createMultiSemaphore($resources, 15);
        $semaphore1->release();
        $semaphore1->release();

        static::assertTrue(true);
    }
}
