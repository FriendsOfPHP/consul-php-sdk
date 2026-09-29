<?php

namespace Consul\Tests\Services;

use Consul\Services\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testLeader(): void
    {
        $leader = (new Status())->leader()->json();

        self::assertIsString($leader);
        self::assertMatchesRegularExpression('/^.+:\d+$/', $leader);
    }

    public function testPeers(): void
    {
        $status = new Status();

        $peers = $status->peers(['dc' => 'dc1'])->json();

        self::assertContains($status->leader()->json(), $peers);
    }
}
