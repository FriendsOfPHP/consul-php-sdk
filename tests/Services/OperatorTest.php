<?php

namespace Consul\Tests\Services;

use Consul\Client;
use Consul\Services\Operator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OperatorTest extends TestCase
{
    private Operator $operator;

    protected function setUp(): void
    {
        $this->operator = new Operator();
    }

    public function testReadRaftConfiguration(): void
    {
        $configuration = $this->operator->readRaftConfiguration(['dc' => 'dc1', 'stale' => true])->json();

        self::assertCount(1, $configuration['Servers']);
        self::assertTrue($configuration['Servers'][0]['Leader']);
        self::assertTrue($configuration['Servers'][0]['Voter']);
    }

    public function testTransferRaftLeader(): void
    {
        $operator = $this->createMockedOperator('POST', 'http://127.0.0.1:8500/v1/operator/raft/transfer-leader?id=09cfc046-e74a-ad49-1aad-c2161b7fe677');

        $response = $operator->transferRaftLeader(['id' => '09cfc046-e74a-ad49-1aad-c2161b7fe677', 'foo' => 'bar']);

        self::assertTrue($response->isSuccessful());
    }

    public function testDeleteRaftPeer(): void
    {
        $operator = $this->createMockedOperator('DELETE', 'http://127.0.0.1:8500/v1/operator/raft/peer?address=1.2.3.4:5678&dc=dc1');

        $response = $operator->deleteRaftPeer(['address' => '1.2.3.4:5678', 'dc' => 'dc1', 'foo' => 'bar']);

        self::assertTrue($response->isSuccessful());
    }

    public function testListKeys(): void
    {
        $operator = $this->createMockedOperator('GET', 'http://127.0.0.1:8500/v1/operator/keyring?relay-factor=2&local-only=1');

        $response = $operator->listKeys(['relay-factor' => 2, 'local-only' => true, 'foo' => 'bar']);

        self::assertTrue($response->isSuccessful());
    }

    public function testInstallKey(): void
    {
        $operator = $this->createMockedOperator('POST', 'http://127.0.0.1:8500/v1/operator/keyring?relay-factor=2', '{"Key":"3lg9DxVfKNzI8O+IQ5Ek+Q=="}');

        $response = $operator->installKey('3lg9DxVfKNzI8O+IQ5Ek+Q==', ['relay-factor' => 2, 'local-only' => true]);

        self::assertTrue($response->isSuccessful());
    }

    public function testUseKey(): void
    {
        $operator = $this->createMockedOperator('PUT', 'http://127.0.0.1:8500/v1/operator/keyring?relay-factor=2', '{"Key":"3lg9DxVfKNzI8O+IQ5Ek+Q=="}');

        $response = $operator->useKey('3lg9DxVfKNzI8O+IQ5Ek+Q==', ['relay-factor' => 2, 'local-only' => true]);

        self::assertTrue($response->isSuccessful());
    }

    public function testRemoveKey(): void
    {
        $operator = $this->createMockedOperator('DELETE', 'http://127.0.0.1:8500/v1/operator/keyring?relay-factor=2', '{"Key":"3lg9DxVfKNzI8O+IQ5Ek+Q=="}');

        $response = $operator->removeKey('3lg9DxVfKNzI8O+IQ5Ek+Q==', ['relay-factor' => 2, 'local-only' => true]);

        self::assertTrue($response->isSuccessful());
    }

    public function testReadAutopilotConfiguration(): void
    {
        $configuration = $this->operator->readAutopilotConfiguration(['dc' => 'dc1', 'stale' => true])->json();

        self::assertArrayHasKey('CleanupDeadServers', $configuration);
        self::assertArrayHasKey('ModifyIndex', $configuration);
    }

    public function testUpdateAutopilotConfiguration(): void
    {
        $original = $this->operator->readAutopilotConfiguration()->json();

        $configuration = $original;
        $configuration['MaxTrailingLogs'] = $original['MaxTrailingLogs'] + 1;

        try {
            // CAS with a stale index: the update is rejected
            $response = $this->operator->updateAutopilotConfiguration($configuration, ['cas' => $original['ModifyIndex'] + 1000]);
            self::assertFalse($response->json());

            $response = $this->operator->updateAutopilotConfiguration($configuration, ['dc' => 'dc1', 'cas' => $original['ModifyIndex']]);
            self::assertTrue($response->json());

            self::assertSame($configuration['MaxTrailingLogs'], $this->operator->readAutopilotConfiguration()->json()['MaxTrailingLogs']);
        } finally {
            $this->operator->updateAutopilotConfiguration($original);
        }
    }

    public function testReadAutopilotHealth(): void
    {
        $health = $this->operator->readAutopilotHealth(['dc' => 'dc1'])->json();

        self::assertTrue($health['Healthy']);
        self::assertCount(1, $health['Servers']);
    }

    public function testReadAutopilotState(): void
    {
        $state = $this->operator->readAutopilotState(['dc' => 'dc1'])->json();

        self::assertTrue($state['Healthy']);
        self::assertArrayHasKey($state['Leader'], $state['Servers']);
    }

    public function testReadUsage(): void
    {
        $usage = $this->operator->readUsage(['global' => true, 'stale' => true])->json();

        self::assertArrayHasKey('dc1', $usage['Usage']);
        self::assertSame(1, $usage['Usage']['dc1']['Nodes']);
    }

    private function createMockedOperator(string $expectedMethod, string $expectedUrl, ?string $expectedBody = null): Operator
    {
        $callback = static function (string $method, string $url, array $options) use ($expectedMethod, $expectedUrl, $expectedBody): MockResponse {
            self::assertSame($expectedMethod, $method);
            self::assertSame($expectedUrl, $url);
            if (null === $expectedBody) {
                self::assertEmpty($options['body'] ?? null);
            } else {
                self::assertSame($expectedBody, $options['body']);
            }

            return new MockResponse('true');
        };

        return new Operator(new Client(client: new MockHttpClient($callback, 'http://127.0.0.1:8500')));
    }
}
