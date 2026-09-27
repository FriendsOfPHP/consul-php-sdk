<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Services\Catalog;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    private Catalog $catalog;

    protected function setUp(): void
    {
        $this->catalog = new Catalog();

        $this->catalog->register([
            'Node' => 'catalog-test-node',
            'Address' => '10.0.0.1',
            'NodeMeta' => ['env' => 'catalog-test'],
            'Service' => [
                'ID' => 'catalog-test-service',
                'Service' => 'catalog-test',
                'Tags' => ['foo'],
                'Port' => 8080,
            ],
        ]);
        $this->catalog->register([
            'Node' => 'catalog-test-node',
            'Address' => '10.0.0.1',
            'NodeMeta' => ['env' => 'catalog-test'],
            'Service' => [
                'ID' => 'catalog-test-proxy',
                'Service' => 'catalog-test-proxy',
                'Kind' => 'connect-proxy',
                'Port' => 21000,
                'Proxy' => ['DestinationServiceName' => 'catalog-test'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->catalog->deregister(['Node' => 'catalog-test-node']);
    }

    public function testDatacenters(): void
    {
        self::assertSame(['dc1'], $this->catalog->datacenters()->json());
        self::assertSame(['dc1'], $this->catalog->datacenters(['cached' => true])->json());
    }

    public function testNodes(): void
    {
        $nodes = array_column($this->catalog->nodes()->json(), 'Node');
        self::assertContains('catalog-test-node', $nodes);

        $response = $this->catalog->nodes(['filter' => 'Meta.env == "catalog-test"']);
        self::assertSame(['catalog-test-node'], array_column($response->json(), 'Node'));
        self::assertArrayHasKey('x-consul-index', $response->getHeaders());
    }

    public function testNode(): void
    {
        $json = $this->catalog->node('catalog-test-node')->json();
        self::assertSame('10.0.0.1', $json['Node']['Address']);
        self::assertArrayHasKey('catalog-test-service', $json['Services']);

        $json = $this->catalog->node('catalog-test-node', ['filter' => 'Kind == "connect-proxy"'])->json();
        self::assertSame(['catalog-test-proxy'], array_keys($json['Services']));
    }

    public function testNodeServices(): void
    {
        $json = $this->catalog->nodeServices('catalog-test-node')->json();
        self::assertSame('catalog-test-node', $json['Node']['Node']);
        self::assertCount(2, $json['Services']);

        $json = $this->catalog->nodeServices('catalog-test-node', ['filter' => 'Service == "catalog-test"'])->json();
        self::assertSame(['catalog-test-service'], array_column($json['Services'], 'ID'));
    }

    public function testServices(): void
    {
        $json = $this->catalog->services()->json();
        self::assertSame(['foo'], $json['catalog-test']);

        $json = $this->catalog->services(['filter' => 'NodeMeta.env == "catalog-test"'])->json();
        self::assertSame(['catalog-test', 'catalog-test-proxy'], array_keys($json));
    }

    public function testService(): void
    {
        $json = $this->catalog->service('catalog-test')->json();
        self::assertSame(['catalog-test-service'], array_column($json, 'ServiceID'));

        self::assertSame([], $this->catalog->service('catalog-test', ['filter' => 'ServicePort == 1'])->json());
    }

    public function testServiceWithManyTags(): void
    {
        $this->catalog->register([
            'Node' => 'catalog-test-node',
            'Address' => '10.0.0.1',
            'Service' => [
                'ID' => 'catalog-test-service-2',
                'Service' => 'catalog-test',
                'Tags' => ['foo', 'bar'],
                'Port' => 8081,
            ],
        ]);

        $json = $this->catalog->service('catalog-test', ['tag' => 'foo'])->json();
        self::assertSame(['catalog-test-service', 'catalog-test-service-2'], array_column($json, 'ServiceID'));

        $json = $this->catalog->service('catalog-test', ['tag' => ['foo', 'bar']])->json();
        self::assertSame(['catalog-test-service-2'], array_column($json, 'ServiceID'));
    }

    public function testNodesWithManyNodeMeta(): void
    {
        self::assertSame(['catalog-test-node'], array_column($this->catalog->nodes(['node-meta' => ['env:catalog-test']])->json(), 'Node'));
        self::assertSame([], $this->catalog->nodes(['node-meta' => ['env:catalog-test', 'rack:unknown']])->json());
    }

    public function testConnect(): void
    {
        $json = $this->catalog->connect('catalog-test')->json();

        self::assertSame(['catalog-test-proxy'], array_column($json, 'ServiceID'));
    }

    public function testGatewayServices(): void
    {
        $client = new Client();
        $client->put('/v1/config', ['json' => [
            'Kind' => 'terminating-gateway',
            'Name' => 'catalog-test-gateway',
            'Services' => [['Name' => 'catalog-test']],
        ]]);

        try {
            $json = $this->catalog->gatewayServices('catalog-test-gateway')->json();
        } finally {
            $client->delete('/v1/config/terminating-gateway/catalog-test-gateway');
        }

        self::assertSame(['catalog-test'], array_column(array_column($json, 'Service'), 'Name'));
    }
}
