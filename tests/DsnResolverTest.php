<?php

namespace Consul\Tests;

use Consul\DsnResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DsnResolverTest extends TestCase
{
    #[DataProvider('provideResolveTest')]
    public function testResolve(string $expected, ?string $dsn): void
    {
        $previousValue = $_SERVER['CONSUL_HTTP_ADDR'] ?? null;

        try {
            unset($_SERVER['CONSUL_HTTP_ADDR']);

            $this->assertSame($expected, DsnResolver::resolve(['base_uri' => $dsn]));
        } finally {
            if (null !== $previousValue) {
                $_SERVER['CONSUL_HTTP_ADDR'] = $previousValue;
            }
        }
    }

    public static function provideResolveTest(): iterable
    {
        yield ['http://127.0.0.1:5000', 'http://127.0.0.1:5000'];
        yield ['https://127.0.0.1:5000', 'https://127.0.0.1:5000'];
        yield ['https://foo:5000', 'https://foo:5000'];
        yield ['http://127.0.0.1:5000', '127.0.0.1:5000'];
        yield ['http://127.0.0.1:5000', '127.0.0.1:5000'];
        yield ['http://127.0.0.1:8500', null];
    }
}
