<?php

namespace Consul\Tests\Services;

use Consul\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class DatacenterOptionTest extends TestCase
{
    // Agent endpoints are local to the agent, listing datacenters does not
    // depend on one, and the catalog (de)registration takes it in the payload
    private const EXCLUDED = [
        'Consul\Services\Agent' => ['*'],
        'Consul\Services\Catalog' => ['datacenters', 'register', 'deregister'],
        'Consul\Services\Coordinate' => ['datacenters'],
    ];

    // Agent endpoints forwarded to the servers
    private const INCLUDED = [
        'Consul\Services\Agent' => ['connectCARoots', 'connectCALeaf'],
    ];

    #[DataProvider('provideMethods')]
    public function testDatacenterOptionIsForwarded(string $class, string $method): void
    {
        $url = null;
        $client = new Client(client: new MockHttpClient(static function (string $method, string $requestUrl) use (&$url) {
            $url = $requestUrl;

            return new MockResponse('true');
        }, 'http://127.0.0.1:8500'));

        $reflection = new \ReflectionMethod($class, $method);
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = match (true) {
                'options' === $parameter->getName() => ['dc' => 'dc2'],
                $parameter->isDefaultValueAvailable() => $parameter->getDefaultValue(),
                default => match ((string) $parameter->getType()) {
                    'array' => [],
                    'bool' => true,
                    default => 'foo',
                },
            };
        }

        $reflection->invokeArgs(new $class($client), $arguments);

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
        self::assertSame('dc2', $query['dc'] ?? null, \sprintf('%s::%s() does not forward the "dc" option.', $class, $method));
    }

    public static function provideMethods(): iterable
    {
        foreach (glob(__DIR__.'/../../src/Services/*.php') as $file) {
            $class = 'Consul\Services\\'.basename($file, '.php');

            foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $name = $method->getName();
                if ('__construct' === $name) {
                    continue;
                }

                $excluded = self::EXCLUDED[$class] ?? [];
                if ((\in_array('*', $excluded, true) || \in_array($name, $excluded, true)) && !\in_array($name, self::INCLUDED[$class] ?? [], true)) {
                    continue;
                }

                yield "{$class}::{$name}" => [$class, $name];
            }
        }
    }
}
