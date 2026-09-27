<?php

namespace Consul\Tests\Services;

use Consul\Services\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    private Event $event;

    protected function setUp(): void
    {
        $this->event = new Event();
    }

    public function testFire(): void
    {
        $name = 'test-event-'.bin2hex(random_bytes(4));

        $event = $this->event->fire($name, 'hello world', ['dc' => 'dc1', 'node' => '.*'])->json();

        self::assertSame($name, $event['Name']);
        self::assertSame('hello world', base64_decode($event['Payload']));
        self::assertSame('.*', $event['NodeFilter']);
    }

    public function testFireWithoutPayload(): void
    {
        $name = 'test-event-'.bin2hex(random_bytes(4));

        $event = $this->event->fire($name)->json();

        self::assertSame($name, $event['Name']);
        self::assertNull($event['Payload']);
    }

    public function testList(): void
    {
        $name = 'test-event-'.bin2hex(random_bytes(4));
        $id = $this->event->fire($name, 'hello world')->json()['ID'];

        // Events are delivered via gossip, so they may take a bit to be listed
        $events = [];
        for ($i = 0; $i < 50 && !$events; ++$i) {
            $events = $this->event->list(['name' => $name])->json();
            if (!$events) {
                usleep(100_000);
            }
        }

        self::assertSame([$id], array_column($events, 'ID'));
        self::assertSame('hello world', base64_decode($events[0]['Payload']));
    }
}
