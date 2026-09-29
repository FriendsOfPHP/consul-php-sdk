<?php

namespace Consul\Tests\Services;

use Consul\Exception\ClientException;
use Consul\Services\Agent;
use Consul\Services\Coordinate;
use PHPUnit\Framework\TestCase;

class CoordinateTest extends TestCase
{
    private Coordinate $coordinate;
    private string $nodeName;

    protected function setUp(): void
    {
        $this->coordinate = new Coordinate();
        $this->nodeName = (new Agent())->self()->json()['Config']['NodeName'];
    }

    public function testDatacenters(): void
    {
        $datacenters = $this->coordinate->datacenters()->json();

        self::assertSame(['dc1'], array_column($datacenters, 'Datacenter'));
    }

    public function testUpdateAndReadNodes(): void
    {
        $vec = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8];

        $response = $this->coordinate->update([
            'Node' => $this->nodeName,
            'Coord' => [
                'Adjustment' => 0,
                'Error' => 1.5,
                'Height' => 0,
                'Vec' => $vec,
            ],
        ], ['dc' => 'dc1']);
        self::assertTrue($response->isSuccessful());

        // Coordinate updates are applied in batches, so we have to wait a bit
        $coordinates = null;
        for ($i = 0; $i < 50; ++$i) {
            try {
                $coordinates = $this->coordinate->node($this->nodeName, ['dc' => 'dc1'])->json();
                if ($vec === $coordinates[0]['Coord']['Vec']) {
                    break;
                }
            } catch (ClientException $e) {
                if (404 !== $e->getCode()) {
                    throw $e;
                }
            }
            usleep(200_000);
        }

        self::assertNotNull($coordinates);
        self::assertSame($this->nodeName, $coordinates[0]['Node']);
        self::assertSame($vec, $coordinates[0]['Coord']['Vec'], 'The coordinate update has not been applied.');

        $nodes = $this->coordinate->nodes(['stale' => true])->json();
        self::assertContains($this->nodeName, array_column($nodes, 'Node'));
    }
}
