<?php

namespace Consul\Helper;

use Consul\Services\KV;
use Consul\Services\Session;

final class LockHandler
{
    private readonly Session $session;
    private readonly KV $kv;
    private ?string $sessionId = null;

    public function __construct(
        private readonly string $key,
        private readonly mixed $value = null,
        ?Session $session = null,
        ?KV $kv = null,
    ) {
        $this->session = $session ?? new Session();
        $this->kv = $kv ?? new KV();
    }

    public function lock(): bool
    {
        // Start a session
        $this->sessionId = $this->session->create()->json()['ID'];

        // Lock a key / value with the current session
        $lockAcquired = $this->kv->put($this->key, (string) $this->value, ['acquire' => $this->sessionId])->json();

        if (false === $lockAcquired) {
            $this->session->destroy($this->sessionId);

            return false;
        }

        register_shutdown_function($this->release(...));

        return true;
    }

    public function release(): void
    {
        $this->kv->delete($this->key);
        $this->session->destroy($this->sessionId);
    }
}
