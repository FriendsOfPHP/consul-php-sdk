<?php

namespace Consul\Tests\Helper;

use Consul\Helper\LockHandler;
use PHPUnit\Framework\TestCase;

class LockHandlerTest extends TestCase
{
    public function testLock(): void
    {
        $lockHandler1 = new LockHandler('test/lock-handler', 'value');
        $lockHandler2 = new LockHandler('test/lock-handler');

        self::assertTrue($lockHandler1->lock());
        self::assertFalse($lockHandler2->lock());

        $lockHandler1->release();

        self::assertTrue($lockHandler2->lock());

        $lockHandler2->release();
    }
}
