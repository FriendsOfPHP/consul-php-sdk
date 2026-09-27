<?php

namespace Consul\Tests\Services;

use Consul\Services\Agent;
use Consul\Services\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private Session $session;
    private string $sessionId;

    protected function setUp(): void
    {
        $this->session = new Session();
        $this->sessionId = $this->session->create(['Name' => 'session-test', 'TTL' => '60s'])->json()['ID'];
    }

    protected function tearDown(): void
    {
        $this->session->destroy($this->sessionId);
    }

    public function testCreate(): void
    {
        $sessionId = $this->session->create()->json()['ID'];

        try {
            self::assertCount(1, $this->session->info($sessionId)->json());
        } finally {
            $this->session->destroy($sessionId);
        }
    }

    public function testDestroy(): void
    {
        $sessionId = $this->session->create()->json()['ID'];

        self::assertSame('true', $this->session->destroy($sessionId)->getBody());
        self::assertSame([], $this->session->info($sessionId)->json());
    }

    public function testInfo(): void
    {
        $response = $this->session->info($this->sessionId, ['consistent' => true]);

        $json = $response->json();
        self::assertSame('session-test', $json[0]['Name']);
        self::assertSame('60s', $json[0]['TTL']);

        $index = $response->getHeaders()['x-consul-index'][0];
        $response = $this->session->info($this->sessionId, ['index' => $index, 'wait' => '10ms']);
        self::assertSame($index, $response->getHeaders()['x-consul-index'][0]);
    }

    public function testNode(): void
    {
        $node = (new Agent())->self()->json()['Config']['NodeName'];

        $json = $this->session->node($node)->json();

        self::assertContains($this->sessionId, array_column($json, 'ID'));
    }

    public function testAll(): void
    {
        $json = $this->session->all(['stale' => true])->json();

        self::assertContains($this->sessionId, array_column($json, 'ID'));
    }

    public function testRenew(): void
    {
        $json = $this->session->renew($this->sessionId)->json();

        self::assertSame($this->sessionId, $json[0]['ID']);
    }
}
