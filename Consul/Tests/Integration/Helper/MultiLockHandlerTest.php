<?php

namespace SensioLabs\Consul\Tests\Integration\Helper;

use SensioLabs\Consul\Helper\MultiLockHandlerFactory;
use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;
use SensioLabs\Consul\Client;
use SensioLabs\Consul\Helper\MultiLockHandler;
use SensioLabs\Consul\ServiceFactory;

class MultiLockHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MultiLockHandlerFactory
     */
    private $multiLockHandlerFactory;

    /**
     *
     */
    public function setUp()
    {
        $serviceFactory = new ServiceFactory(['base_url' => 'http://127.0.0.1:8500']);

        $this->multiLockHandlerFactory = new MultiLockHandlerFactory(
            $serviceFactory->get('session'),
            $serviceFactory->get('kv'),
            'test/lock/'
        );
    }

    /**
     *
     */
    public function testLockTheSameResource()
    {
        $resources = ['resource1', 'resource2'];

        $multiLockHandler1 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $multiLockHandler2 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);

        $this->assertTrue($multiLockHandler1->lock());
        $this->assertFalse($multiLockHandler2->lock());

        $multiLockHandler1->release();
    }

    /**
     *
     */
    public function testLockDifferentSameResource()
    {
        $multiLockHandler1 = $this->multiLockHandlerFactory->createMultiLockHandler(['resource1', 'resource2', 'resource3'], 10);
        $multiLockHandler2 = $this->multiLockHandlerFactory->createMultiLockHandler(['resource4', 'resource5', 'resource6'], 10);
        $multiLockHandler3 = $this->multiLockHandlerFactory->createMultiLockHandler(['resource7', 'resource8'], 10);

        $this->assertTrue($multiLockHandler1->lock());
        $this->assertTrue($multiLockHandler2->lock());
        $this->assertTrue($multiLockHandler3->lock());

        $multiLockHandler1->release();
        $multiLockHandler2->release();
        $multiLockHandler3->release();
    }

    /**
     *
     */
    public function testRenew()
    {
        $resources = ['resource1', 'resource2'];
        $multiLockHandler1 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $multiLockHandler2 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $multiLockHandler3 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $multiLockHandler4 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);

        $this->assertTrue($multiLockHandler1->lock());
        $this->assertFalse($multiLockHandler2->lock());
        $this->assertFalse($multiLockHandler3->lock());
        $this->assertFalse($multiLockHandler4->lock());

        sleep(8);

        $this->assertTrue($multiLockHandler1->renew());

        sleep(8);

        $this->assertFalse($multiLockHandler2->lock());
        $this->assertFalse($multiLockHandler3->lock());
        $this->assertFalse($multiLockHandler4->lock());

        sleep(15);

        $this->assertTrue($multiLockHandler2->lock());
        $this->assertFalse($multiLockHandler3->lock());
        $this->assertFalse($multiLockHandler4->lock());

        $multiLockHandler1->release();
        $multiLockHandler2->release();
    }

    /**
     * @expectedException \SensioLabs\Consul\Exception\ClientException
     */
    public function testRenewExpiredSession()
    {
        $resources = ['resource1', 'resource2'];
        $multiLockHandler = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $this->assertTrue($multiLockHandler->lock());
        sleep(21);
        $multiLockHandler->renew();
    }

    /**
     *
     */
    public function testRelease()
    {
        $resources = ['resource1', 'resource2'];

        $multiLockHandler1 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);
        $multiLockHandler2 = $this->multiLockHandlerFactory->createMultiLockHandler($resources, 10);

        $this->assertTrue($multiLockHandler1->lock());
        $this->assertFalse($multiLockHandler2->lock());

        $multiLockHandler1->release();

        $this->assertTrue($multiLockHandler2->lock());

        $multiLockHandler2->release();
    }
}
