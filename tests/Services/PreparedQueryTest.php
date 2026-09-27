<?php

namespace Consul\Tests\Services;

use Consul\Exception\ClientException;
use Consul\Services\Agent;
use Consul\Services\PreparedQuery;
use PHPUnit\Framework\TestCase;

class PreparedQueryTest extends TestCase
{
    private PreparedQuery $preparedQuery;
    private string $name;
    private ?string $queryId = null;

    protected function setUp(): void
    {
        $this->preparedQuery = new PreparedQuery();
        $this->name = 'test-query-'.bin2hex(random_bytes(4));

        (new Agent())->registerService(['ID' => $this->name, 'Name' => $this->name]);
    }

    protected function tearDown(): void
    {
        (new Agent())->deregisterService($this->name);

        if (null !== $this->queryId) {
            try {
                $this->preparedQuery->delete($this->queryId);
            } catch (ClientException) {
            }
        }
    }

    public function testCreate(): void
    {
        $response = $this->preparedQuery->create($this->getDefinition(), ['dc' => 'dc1']);
        $this->queryId = $response->json()['ID'];

        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $this->queryId);
    }

    public function testList(): void
    {
        $this->queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $queries = $this->preparedQuery->list(['dc' => 'dc1', 'stale' => true])->json();

        self::assertContains($this->queryId, array_column($queries, 'ID'));
    }

    public function testRead(): void
    {
        $this->queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $queries = $this->preparedQuery->read($this->queryId, ['consistent' => true])->json();

        self::assertCount(1, $queries);
        self::assertSame($this->name, $queries[0]['Name']);
        self::assertSame($this->name, $queries[0]['Service']['Service']);
    }

    public function testUpdate(): void
    {
        $this->queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $definition = $this->getDefinition();
        $definition['Service']['Tags'] = ['foo'];
        $response = $this->preparedQuery->update($this->queryId, $definition, ['dc' => 'dc1']);

        self::assertTrue($response->isSuccessful());
        self::assertSame(['foo'], $this->preparedQuery->read($this->queryId)->json()[0]['Service']['Tags']);
    }

    public function testDelete(): void
    {
        $queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $response = $this->preparedQuery->delete($queryId, ['dc' => 'dc1']);
        self::assertTrue($response->isSuccessful());

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(404);

        $this->preparedQuery->read($queryId);
    }

    public function testExecute(): void
    {
        $this->queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $result = $this->preparedQuery->execute($this->queryId, ['limit' => 1])->json();
        self::assertSame($this->name, $result['Service']);
        self::assertSame([$this->name], array_map(static fn (array $node) => $node['Service']['ID'], $result['Nodes']));

        // Queries can be executed by name, too
        $result = $this->preparedQuery->execute($this->name)->json();
        self::assertSame($this->name, $result['Service']);
    }

    public function testExplain(): void
    {
        $this->queryId = $this->preparedQuery->create($this->getDefinition())->json()['ID'];

        $result = $this->preparedQuery->explain($this->queryId, ['dc' => 'dc1'])->json();

        self::assertSame($this->queryId, $result['Query']['ID']);
        self::assertSame($this->name, $result['Query']['Service']['Service']);
    }

    private function getDefinition(): array
    {
        return [
            'Name' => $this->name,
            'Service' => [
                'Service' => $this->name,
                'OnlyPassing' => true,
            ],
        ];
    }
}
