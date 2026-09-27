<?php

namespace Consul\Tests\Services;

use Consul\Exception\ClientException;
use Consul\Services\Connect;
use PHPUnit\Framework\TestCase;

class ConnectTest extends TestCase
{
    private Connect $connect;

    protected function setUp(): void
    {
        $this->connect = new Connect();
    }

    protected function tearDown(): void
    {
        $this->connect->deleteIntention('sdk-test-web', 'sdk-test-db');
    }

    public function testListCARoots(): void
    {
        $roots = $this->connect->listCARoots()->json();

        self::assertArrayHasKey('ActiveRootID', $roots);
        self::assertNotEmpty($roots['Roots']);
    }

    public function testListCARootsAsPem(): void
    {
        $response = $this->connect->listCARoots(['pem' => true]);

        self::assertStringStartsWith('-----BEGIN CERTIFICATE-----', $response->getBody());
    }

    public function testReadCAConfiguration(): void
    {
        $configuration = $this->connect->readCAConfiguration()->json();

        self::assertSame('consul', $configuration['Provider']);
    }

    public function testUpdateCAConfiguration(): void
    {
        $configuration = $this->connect->readCAConfiguration()->json();
        $previousTtl = $configuration['Config']['LeafCertTTL'];

        try {
            $response = $this->connect->updateCAConfiguration([
                'Provider' => 'consul',
                'Config' => ['LeafCertTTL' => '48h'],
            ]);
            self::assertTrue($response->isSuccessful());

            self::assertSame('48h', $this->connect->readCAConfiguration()->json()['Config']['LeafCertTTL']);
        } finally {
            $this->connect->updateCAConfiguration([
                'Provider' => 'consul',
                'Config' => ['LeafCertTTL' => $previousTtl],
            ]);
        }
    }

    public function testUpsertIntention(): void
    {
        $response = $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'allow', 'Description' => 'SDK test']);

        self::assertTrue($response->json());
    }

    public function testReadIntention(): void
    {
        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'deny']);

        $intention = $this->connect->readIntention('sdk-test-web', 'sdk-test-db')->json();

        self::assertSame('sdk-test-web', $intention['SourceName']);
        self::assertSame('sdk-test-db', $intention['DestinationName']);
        self::assertSame('deny', $intention['Action']);
    }

    public function testListIntentions(): void
    {
        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'allow']);

        $intentions = $this->connect->listIntentions(['filter' => 'SourceName == "sdk-test-web"'])->json();

        self::assertCount(1, $intentions);
        self::assertSame('sdk-test-db', $intentions[0]['DestinationName']);
    }

    public function testDeleteIntention(): void
    {
        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'allow']);

        $response = $this->connect->deleteIntention('sdk-test-web', 'sdk-test-db');
        self::assertTrue($response->isSuccessful());

        $this->expectException(ClientException::class);
        $this->expectExceptionMessageMatches('/404/');

        $this->connect->readIntention('sdk-test-web', 'sdk-test-db');
    }

    public function testCheckIntention(): void
    {
        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'deny']);

        self::assertFalse($this->connect->checkIntention('sdk-test-web', 'sdk-test-db')->json()['Allowed']);

        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'allow']);

        self::assertTrue($this->connect->checkIntention('sdk-test-web', 'sdk-test-db')->json()['Allowed']);
    }

    public function testMatchIntentions(): void
    {
        $this->connect->upsertIntention('sdk-test-web', 'sdk-test-db', ['Action' => 'allow']);

        $matches = $this->connect->matchIntentions('destination', 'sdk-test-db')->json();

        self::assertArrayHasKey('sdk-test-db', $matches);
        self::assertSame('sdk-test-web', $matches['sdk-test-db'][0]['SourceName']);
    }
}
