<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Services\Catalog;
use Consul\Services\Health;
use PHPUnit\Framework\TestCase;

class HealthTest extends TestCase
{
    private Health $health;
    private Catalog $catalog;

    protected function setUp(): void
    {
        $this->health = new Health();
        $this->catalog = new Catalog();

        $this->catalog->register([
            'Node' => 'health-test-node',
            'Address' => '10.0.0.1',
            'NodeMeta' => ['env' => 'health-test'],
            'Service' => [
                'ID' => 'health-test-service',
                'Service' => 'health-test',
                'Tags' => ['foo'],
                'Port' => 8080,
            ],
            'Check' => [
                'CheckID' => 'health-test-check',
                'Name' => 'health-test-check',
                'Status' => 'warning',
                'ServiceID' => 'health-test-service',
            ],
        ]);
        $this->catalog->register([
            'Node' => 'health-test-node',
            'Address' => '10.0.0.1',
            'NodeMeta' => ['env' => 'health-test'],
            'Service' => [
                'ID' => 'health-test-proxy',
                'Service' => 'health-test-proxy',
                'Kind' => 'connect-proxy',
                'Port' => 21000,
                'Proxy' => ['DestinationServiceName' => 'health-test'],
            ],
        ]);
        $this->catalog->register([
            'Node' => 'health-test-node',
            'Address' => '10.0.0.1',
            'NodeMeta' => ['env' => 'health-test'],
            'Service' => [
                'ID' => 'health-test-ingress',
                'Service' => 'health-test-ingress',
                'Kind' => 'ingress-gateway',
                'Port' => 21001,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->catalog->deregister(['Node' => 'health-test-node']);
    }

    public function testNode(): void
    {
        $json = $this->health->node('health-test-node')->json();
        self::assertSame(['health-test-check'], array_column($json, 'CheckID'));

        $response = $this->health->node('health-test-node', ['filter' => 'Status == "passing"']);
        self::assertSame([], $response->json());
        self::assertArrayHasKey('x-consul-index', $response->getHeaders());
    }

    public function testChecks(): void
    {
        $json = $this->health->checks('health-test')->json();
        self::assertSame(['warning'], array_column($json, 'Status'));

        self::assertSame([], $this->health->checks('health-test', ['filter' => 'Status == "passing"'])->json());
    }

    public function testService(): void
    {
        $json = $this->health->service('health-test')->json();
        self::assertSame(['health-test-service'], array_column(array_column($json, 'Service'), 'ID'));

        self::assertSame([], $this->health->service('health-test', ['passing' => true])->json());
        self::assertSame([], $this->health->service('health-test', ['filter' => 'Service.Port == 1'])->json());
    }

    public function testConnect(): void
    {
        $json = $this->health->connect('health-test')->json();

        self::assertSame(['health-test-proxy'], array_column(array_column($json, 'Service'), 'ID'));
    }

    public function testIngress(): void
    {
        $client = new Client();
        $client->put('/v1/config', ['json' => [
            'Kind' => 'ingress-gateway',
            'Name' => 'health-test-ingress',
            'Listeners' => [['Port' => 9999, 'Protocol' => 'tcp', 'Services' => [['Name' => 'health-test']]]],
        ]]);

        try {
            $json = $this->health->ingress('health-test')->json();
        } finally {
            $client->delete('/v1/config/ingress-gateway/health-test-ingress');
        }

        self::assertSame(['health-test-ingress'], array_column(array_column($json, 'Service'), 'ID'));
    }

    public function testState(): void
    {
        $json = $this->health->state('warning')->json();
        self::assertContains('health-test-check', array_column($json, 'CheckID'));

        $json = $this->health->state('any', ['filter' => 'Node == "health-test-node"'])->json();
        self::assertSame(['health-test-check'], array_column($json, 'CheckID'));
    }
}
