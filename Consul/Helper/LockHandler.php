<?php

namespace SensioLabs\Consul\Helper;

use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Services\KV;
use SensioLabs\Consul\Services\Session;

class LockHandler
{
    /** @var  string */
    private $key;
    /** @var string */
    private $value;
    /** @var Session */
    private $sessionService;
    /** @var KV */
    private $kvService;

    /** @var string */
    private $sessionId;

    /** @var  bool */
    private $lockAcquired = false;

    /** @var bool */
    private $shutdownRegistered = false;

    /** @var string */
    private $sessionTTL = '0s';

    /** @var string */
    private $lockDelay = '15s';

    /** @var bool */
    private $ephemeral = true;

    /**
     * LockHandler provides a convienient way to acquire advisory distributed locks using Consul. Once a lock is
     * obtained only the session held by this particular LockHandler will be able to update the locked key, other
     * LockHandlers will be denied/blocked. The LockHandler Helper must be used to ensure locking works. Updates to keys
     * via the KV service will ignore the advisory lock.
     *
     * Optionally the LockHander will block for up to 10 minutes at while waiting for a lock to be released.
     *
     *
     * @param string $key
     * @param string $value
     * @param Session|null $session
     * @param KV|null $kv
     */
    public function __construct($key, $value = null, Session $session = null, KV $kv = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->sessionService = $session ?: new Session();
        $this->kvService = $kv ?: new KV();
    }

    /**
     * Perform the actual lock. This can be called multiple times. If the lock is already acquired the lock will be
     * retained and the value will be updated to the current value. If the lock is not yet acquired another attempt to
     * acquire will be made. Specifying the $wait argument will cause the acquisition to block as described in
     * "Blocking Queries" in https://www.consul.io/docs/agent/http.html This value must be specified in seconds.
     *
     * The wait will round up during blocking, so there is a chance that it will take slightly longer than the
     * specified number of seconds to return.
     *
     * @param float $wait in seconds
     * @return bool
     */
    public function lock($wait = 0.0)
    {
        $end_time = microtime(true) + $wait;
        $modifyIndex = 0;
        // If we are going to block while waiting for the lock
        if ($wait > 0.0) {
            // This is when we should stop trying to get a lock.
            // we need to know what the modify index is before we start, so that we can wait on a change if it the lock
            // is not yet available.
            try {
                $modifyIndex = $this->kvService->get($this->key)->json()[0]['ModifyIndex'];
            } catch (ClientException $exception) {
                if (404 != $exception->getCode()) { // It is OK if it doesn't exist.
                    throw $exception; // Everything else is a problem.
                }
            }
        }

        // Attempt to get the lock
        if (false === ($this->lockAcquired = $this->kvService->put(
                $this->key,
                (string)$this->value,
                ['acquire' => $this->getSessionId()]
            )->json())
        ) {
            // We didn't get the lock this time, but we still have time to wait.
            if (microtime(true) < $end_time) {
                // Deal with the blocking call - this will block for up to $wait for the key to be modified.
                $wait_whole_seconds = round($wait, 0, PHP_ROUND_HALF_UP);
                try {
                    $this->kvService->get($this->key, ['index' => $modifyIndex, 'wait' => "{$wait_whole_seconds}s"]);
                } catch (ClientException $exception) {
                    if (404 != $exception->getCode()) { // It is OK if the key has been deleted.
                        throw $exception; // Everything else is a problem.
                    }
                }

                return $this->lock($end_time - microtime(true)); // Try getting lock again with revised wait.
            } else {
                // We aren't waiting so immediately return false.
                return false;
            }
        }

        if (false === $this->shutdownRegistered) {
            register_shutdown_function([$this, 'release']);
            $this->shutdownRegistered = true;
        }

        return true;
    }

    /**
     * Release the lock. There are two possible ways of doing this. The default ephemeral mode will delete the
     * key and destroy the session leaving no evidence of the lock.
     * If the LockHandler is set to be non-ephemeral (permanent), the lock will be released and any provided value
     * will be written to the key at the same time. If no value or null is provided the existing value
     * will be used again.
     *
     * @param string $value
     * @return void
     */
    public function release($value = null)
    {
        // For ephemeral locks the key and session will be deleted/destroyed at "release" time.
        if (true === $this->isEphemeral() && true === $this->lockAcquired) {
            $this->kvService->put($this->key, $value, ['release' => $this->getSessionId()]);
            $this->kvService->delete($this->key);
            $this->sessionService->destroy($this->sessionId);
            $this->sessionId = null;
            $this->lockAcquired = false;

            return;
        }

        // For permanent locks the key/value remains in the KV store.
        if (true === $this->lockAcquired) { // If we have the lock
            if (null !== $value) { // Value is being changed on release.
                $this->value = $value;
            }
            $this->kvService->put($this->key, $this->value, ['release' => $this->getSessionId()]);
            $this->lockAcquired = false;
        }

        return;
    }

    /**
     * Upadte the locally held copy of the value. If the lock is currently held the KV store will also be updated.
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        if ($this->lockAcquired) {
            $this->lock(); // update the value in the kv store.
        }
    }

    public function getSessionId()
    {
        if (!$this->sessionId) {
            // For ephemeral locks we want the non-default behaviour of delete so that the lock is deleted on
            // invalidation rather than just released.
            // This matches the behaviour of calling an explicit release on an ephemeral lock.
            $behaviour = 'release';
            if ($this->isEphemeral()) {
                $behaviour = 'delete';
            }

            // Start a session. This will stay with this lock handler so that the value can be updated without dropping and
            // re-acquiring lock as per https://www.consul.io/docs/agent/http/kv.html#_acquire_lt_session_gt_
            $session = $this->sessionService->create(
                [
                    'LockDelay' => $this->lockDelay,
                    'TTL'       => $this->sessionTTL,
                    'Behavior'  => $behaviour,
                ]
            )->json();
            $this->sessionId = $session['ID'];
        }

        return $this->sessionId;

    }

    /**
     * @param string $sessionTTL
     */
    public function setSessionTTL($sessionTTL)
    {
        $this->sessionTTL = $sessionTTL;
    }

    /**
     * @param string $lockDelay
     */
    public function setLockDelay($lockDelay)
    {
        $this->lockDelay = $lockDelay;
    }

    /**
     * @return bool
     */
    public function isEphemeral()
    {
        return $this->ephemeral;
    }

    /**
     * @param bool $ephemeral
     */
    public function setEphemeral($ephemeral)
    {
        $this->ephemeral = $ephemeral;
    }

}
