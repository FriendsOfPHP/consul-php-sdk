<?php
/**
 * Created by PhpStorm.
 * User: steve.hall
 * Date: 20/03/2017
 * Time: 16:23
 */

namespace SensioLabs\Consul\Tests\Helper;

use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ConsulExceptionInterface;
use SensioLabs\Consul\Helper\LockHandler;
use SensioLabs\Consul\ServiceFactory;
use SensioLabs\Consul\Services\KV;


class LockHandlerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ServiceFactory
     */
    private $sf;
    /** @var  string */
    private $prefix;

    /** @var  KV */
    private $kv;


    /**
     * Establish shared resources.
     */
    public function setUp()
    {
        $this->sf = new ServiceFactory(['base_url' => 'http://127.0.0.1:8500']);
        /** @var KV kv */
        $this->kv = $this->sf->get('kv');
        // Start with a new prefix each time, so left overs from previous test runs don't pollute results
        $this->prefix = 'testing_sensio_consul_lockhandler_'.microtime(true);

    }

    /**
     * For tidiness sake remove the testing prefix.
     */
    protected function tearDown()
    {
        parent::tearDown();
        try {
            $this->kv->delete($this->prefix);
        } catch (\Exception $e) {
            // don't care if this failed - we tried to be tidy!
        }
    }

    public function testPermanentExclusiveLock()
    {

        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->kv
        );

        $lockHandler1->setEphemeral(false);

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->kv
        );
        $lockHandler2->setEphemeral(false);

        // Should be able to get one lock
        static::assertTrue($lockHandler1->lock());

        // And the value should have been set.
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // But cannot get a lock on the same key with a different session.
        static::assertFalse($lockHandler2->lock());

        // And the value should have stayed the sam.
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody() );
        static::assertNotEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Once the first lock is released
        $lockHandler1->release();
        // No change happens to the value
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertNotEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Until we lock using the second handler
        static::assertTrue($lockHandler2->lock());

        // Now the value has changed.
        static::assertNotEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Lock 1 should not be able to release
        $lockHandler1->release('lock 1 release value'); // No effect
        static::assertNotEquals('lock 1 release value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Lock 2 can release and changes the value on release.
        $lockHandler2->release('lock 2 release value'); // Changes value and releases at the same time.
        static::assertEquals('lock 2 release value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

    }

    public function testEphemeralExclusiveLock()
    {

        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->kv
        );

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->kv
        );

        // Should be able to get one lock
        static::assertTrue($lockHandler1->lock());

        // And the value should have been set.
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // But cannot get a lock on the same key with a different session.
        static::assertFalse($lockHandler2->lock());

        // And the value should have stayed the sam.
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertNotEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Once the first lock is released
        $lockHandler1->release();

        // the key should be gone which means we should get a ClientException with 404 code.
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(404);
        $this->kv->get($this->prefix);

        // Until we lock using the second handler
        static::assertTrue($lockHandler2->lock());

        // Now the value exists again and is the right thing.
        static::assertNotEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());


    }

    public function testEphemeralLockOwnership()
    {
        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );

        // Should be able to get one lock
        static::assertTrue($lockHandler1->lock());
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());


        // But not a second on the same key.
        static::assertFalse($lockHandler2->lock());

        // but should be fine to get the lock again with the existing handler
        $lockHandler1->setValue('a new lock value');
        static::assertTrue($lockHandler1->lock());
        static::assertNotEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertEquals('a new lock value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // And still only need to release once
        $lockHandler1->release();

        // After release the key / value is gone
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(404);
        $this->kv->get($this->prefix);

        // A second thread can now acquire with new value.
        static::assertTrue($lockHandler2->lock());
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
    }


    public function testPermanentLockOwnership()
    {
        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );
        $lockHandler1->setEphemeral(false);

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );
        $lockHandler2->setEphemeral(false);

        // Should be able to get one lock
        static::assertTrue($lockHandler1->lock());
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());


        // But not a second on the same key.
        static::assertFalse($lockHandler2->lock());

        // but should be fine to get the lock again with the existing handler
        $lockHandler1->setValue('a new lock value');
        static::assertTrue($lockHandler1->lock());
        static::assertNotEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
        static::assertEquals('a new lock value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // And still only need to release once
        $lockHandler1->release();

        // After release the new value remains
        static::assertEquals('a new lock value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // A second thread can now acquire with new value.
        static::assertTrue($lockHandler2->lock());
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());
    }


    /**
     * We can't reliably multithread in this test, so blocking locks are tested by cheating slightly
     * and setting a low TTL for the first lock obtained. After the TTL expires, Consul invalidates the session and
     * the lock is released. At that point our blocking lock will be able to acquire.
     * According to consul documentation on https://www.consul.io/docs/agent/http/session.html the minimum TTL is 10s
     * and actual invalidation can take up to double this. So we will wait up to a maximum of 20s for the lock.
     *
     */
    public function testBlockingPermanentLocks()
    {
        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );
        $lockHandler1->setEphemeral(false);

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );
        $lockHandler2->setEphemeral(false);


        // Set the lowest TTL possible.
        $lockHandler1->setSessionTTL('10s');
        // Because we are relying upon session invalidation the LockDelay will kick in. Setting this to 0s prevents it
        // from delaying our test.
        $lockHandler1->setLockDelay('0s');

        // Acquire the lock. In somewhere between 10s and 21s this should be invalidated and the lock released.
        static::assertTrue($lockHandler1->lock(1));
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // First set a wait that isn't long enough to actually get the lock.
        $start_time = microtime(true);
        static::assertFalse($lockHandler2->lock(5));
        $duration = microtime(true) - $start_time;
        static::assertGreaterThanOrEqual(4, $duration);
        static::assertLessThanOrEqual(6, $duration);
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Now wait long enough for the session to invalidate
        static::assertTrue($lockHandler2->lock(20));
        $duration = microtime(true) - $start_time;
        static::assertGreaterThanOrEqual(10, $duration);
        static::assertLessThanOrEqual(21, $duration);
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

    }

    public function testBlockingEphemeralLocks()
    {
        $lockHandler1 = new LockHandler(
            $this->prefix,
            'lock1 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );

        $lockHandler2 = new LockHandler(
            $this->prefix,
            'lock2 value',
            $this->sf->get('session'),
            $this->sf->get('kv')
        );


        // Set the lowest TTL possible.
        $lockHandler1->setSessionTTL('10s');
        // Because we are relying upon session invalidation the LockDelay will kick in. Setting this to 0s prevents it
        // from delaying our test.
        $lockHandler1->setLockDelay('0s');

        // Acquire the lock. In somewhere between 10s and 21s this should be invalidated and the lock released.
        static::assertTrue($lockHandler1->lock(1));
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // First set a wait that isn't long enough to actually get the lock.
        $start_time = microtime(true);
        static::assertFalse($lockHandler2->lock(5));
        $duration = microtime(true) - $start_time;
        static::assertGreaterThanOrEqual(4, $duration);
        static::assertLessThanOrEqual(6, $duration);
        static::assertEquals('lock1 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        // Now wait long enough for the session to invalidate
        static::assertTrue($lockHandler2->lock(20));
        $duration = microtime(true) - $start_time;
        static::assertGreaterThanOrEqual(10, $duration);
        static::assertLessThanOrEqual(21, $duration);
        static::assertEquals('lock2 value', $this->kv->get($this->prefix, ['raw'=>true])->getBody());

        $lockHandler2->release();
        // After release the key / value is gone
        $this->expectException(ClientException::class);
        $this->expectExceptionCode(404);
        $this->kv->get($this->prefix);

    }


}