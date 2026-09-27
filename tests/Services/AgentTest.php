<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Exception\ClientException;
use Consul\Exception\ServerException;
use Consul\Services\Agent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AgentTest extends TestCase
{
    private Agent $agent;

    protected function setUp(): void
    {
        $this->agent = new Agent();
    }

    protected function tearDown(): void
    {
        try {
            $this->agent->deregisterService('agent-test-service');
        } catch (ClientException) {
        }

        try {
            $this->agent->deregisterCheck('agent-test-check');
        } catch (ClientException) {
        }
    }

    public function testHost(): void
    {
        $json = $this->agent->host()->json();

        self::assertArrayHasKey('Host', $json);
    }

    public function testVersion(): void
    {
        $json = $this->agent->version()->json();

        self::assertArrayHasKey('HumanVersion', $json);
    }

    public function testSelf(): void
    {
        $json = $this->agent->self()->json();

        self::assertArrayHasKey('Config', $json);
    }

    public function testMembers(): void
    {
        $json = $this->agent->members()->json();

        self::assertCount(1, $json);
    }

    public function testMetrics(): void
    {
        $json = $this->agent->metrics()->json();

        self::assertArrayHasKey('Gauges', $json);
    }

    public function testMaintenance(): void
    {
        $this->agent->maintenance(true, ['reason' => 'agent-test']);
        $checks = $this->agent->checks()->json();
        self::assertArrayHasKey('_node_maintenance', $checks);
        self::assertSame('agent-test', $checks['_node_maintenance']['Notes']);

        $this->agent->maintenance(false);
        self::assertArrayNotHasKey('_node_maintenance', $this->agent->checks()->json());
    }

    public function testRegisterAndDeregisterService(): void
    {
        $this->agent->registerService(['ID' => 'agent-test-service', 'Name' => 'agent-test', 'Tags' => ['foo']], ['replace-existing-checks' => true]);

        $services = $this->agent->services()->json();
        self::assertArrayHasKey('agent-test-service', $services);

        $services = $this->agent->services(['filter' => 'Service == "agent-test"'])->json();
        self::assertSame(['agent-test-service'], array_keys($services));

        $services = $this->agent->services(['filter' => 'Service == "not-agent-test"'])->json();
        self::assertSame([], $services);

        $this->agent->deregisterService('agent-test-service');
        self::assertArrayNotHasKey('agent-test-service', $this->agent->services()->json());
    }

    public function testService(): void
    {
        $this->agent->registerService(['ID' => 'agent-test-service', 'Name' => 'agent-test']);

        $response = $this->agent->service('agent-test-service');
        self::assertSame('agent-test', $response->json()['Service']);
        self::assertArrayHasKey('x-consul-contenthash', $response->getHeaders());
    }

    public function testServiceMaintenance(): void
    {
        $this->agent->registerService(['ID' => 'agent-test-service', 'Name' => 'agent-test']);

        $this->agent->serviceMaintenance('agent-test-service', true, ['reason' => 'agent-test']);
        $checks = $this->agent->checks(['filter' => 'ServiceID == "agent-test-service"'])->json();
        self::assertArrayHasKey('_service_maintenance:agent-test-service', $checks);

        $this->agent->serviceMaintenance('agent-test-service', false);
        $checks = $this->agent->checks(['filter' => 'ServiceID == "agent-test-service"'])->json();
        self::assertSame([], $checks);
    }

    public function testHealthService(): void
    {
        $this->agent->registerService(['ID' => 'agent-test-service', 'Name' => 'agent-test']);

        self::assertSame('passing', $this->agent->healthServiceById('agent-test-service')->json()['AggregatedStatus']);
        self::assertSame('passing', $this->agent->healthServiceById('agent-test-service', ['format' => 'text'])->getBody());
        self::assertSame('passing', $this->agent->healthServiceByName('agent-test')->json()[0]['AggregatedStatus']);
        self::assertSame('passing', $this->agent->healthServiceByName('agent-test', ['format' => 'text'])->getBody());

        $this->agent->registerService(['ID' => 'agent-test-service', 'Name' => 'agent-test', 'Check' => ['TTL' => '10m']]);

        $this->expectException(ServerException::class);
        $this->expectExceptionCode(503);

        $this->agent->healthServiceById('agent-test-service');
    }

    public function testChecks(): void
    {
        $this->agent->registerCheck(['ID' => 'agent-test-check', 'Name' => 'agent-test', 'TTL' => '10m']);

        $check = $this->agent->checks()->json()['agent-test-check'];
        self::assertSame('critical', $check['Status']);

        $this->agent->passCheck('agent-test-check', ['note' => 'pass']);
        $check = $this->agent->checks()->json()['agent-test-check'];
        self::assertSame('passing', $check['Status']);
        self::assertSame('pass', $check['Output']);

        $this->agent->warnCheck('agent-test-check', ['note' => 'warn']);
        $check = $this->agent->checks()->json()['agent-test-check'];
        self::assertSame('warning', $check['Status']);
        self::assertSame('warn', $check['Output']);

        $this->agent->failCheck('agent-test-check', ['note' => 'fail']);
        $check = $this->agent->checks()->json()['agent-test-check'];
        self::assertSame('critical', $check['Status']);
        self::assertSame('fail', $check['Output']);

        $this->agent->updateCheck('agent-test-check', ['Status' => 'passing', 'Output' => 'update']);
        $check = $this->agent->checks(['filter' => 'CheckID == "agent-test-check"'])->json()['agent-test-check'];
        self::assertSame('passing', $check['Status']);
        self::assertSame('update', $check['Output']);

        $this->agent->deregisterCheck('agent-test-check');
        self::assertArrayNotHasKey('agent-test-check', $this->agent->checks()->json());
    }

    public function testConnectCARoots(): void
    {
        $json = $this->agent->connectCARoots()->json();

        self::assertNotEmpty($json['Roots']);
    }

    public function testConnectCALeaf(): void
    {
        $json = $this->agent->connectCALeaf('agent-test')->json();

        self::assertSame('agent-test', $json['Service']);
        self::assertStringContainsString('BEGIN CERTIFICATE', $json['CertPEM']);
    }

    public function testConnectAuthorize(): void
    {
        $trustDomain = $this->agent->connectCARoots()->json()['TrustDomain'];

        $json = $this->agent->connectAuthorize([
            'Target' => 'agent-test',
            'ClientCertURI' => \sprintf('spiffe://%s/ns/default/dc/dc1/svc/web', $trustDomain),
            'ClientCertSerial' => '04:00:00:00:00:01:15:4b:5a:c3:94',
        ])->json();

        self::assertArrayHasKey('Authorized', $json);
    }

    public function testJoin(): void
    {
        $agent = $this->createMockedAgent('PUT', 'http://127.0.0.1:8500/v1/agent/join/10.0.0.1?wan=1');

        $agent->join('10.0.0.1', ['wan' => true, 'foo' => 'bar']);
    }

    public function testLeave(): void
    {
        $agent = $this->createMockedAgent('PUT', 'http://127.0.0.1:8500/v1/agent/leave');

        $agent->leave();
    }

    public function testForceLeave(): void
    {
        $agent = $this->createMockedAgent('PUT', 'http://127.0.0.1:8500/v1/agent/force-leave/node1?prune=1&wan=1');

        $agent->forceLeave('node1', ['prune' => true, 'wan' => true, 'foo' => 'bar']);
    }

    public function testReload(): void
    {
        $agent = $this->createMockedAgent('PUT', 'http://127.0.0.1:8500/v1/agent/reload');

        $agent->reload();
    }

    public function testUpdateToken(): void
    {
        $agent = $this->createMockedAgent('PUT', 'http://127.0.0.1:8500/v1/agent/token/default', '{"Token":"secret"}');

        $agent->updateToken('default', ['Token' => 'secret']);
    }

    private function createMockedAgent(string $method, string $url, ?string $body = null): Agent
    {
        $callback = static function (string $actualMethod, string $actualUrl, array $options) use ($method, $url, $body): MockResponse {
            self::assertSame($method, $actualMethod);
            self::assertSame($url, $actualUrl);
            if (null !== $body) {
                self::assertSame($body, $options['body']);
            }

            return new MockResponse('true');
        };

        return new Agent(new Client(client: new MockHttpClient($callback, 'http://127.0.0.1:8500')));
    }
}
