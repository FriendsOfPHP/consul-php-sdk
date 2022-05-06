<?php

namespace Consul\Tests;

use Consul\OptionsResolver;
use PHPUnit\Framework\TestCase;

class OptionsResolverTest extends TestCase
{
    public function testResolve()
    {
        $options = [
            'foo' => 'bar',
            'hello' => 'world',
            'baz' => 'inga',
        ];

        $availableOptions = [
            'foo', 'baz',
        ];

        $result = OptionsResolver::resolve($options, $availableOptions);

        $expected = [
            'foo' => 'bar',
            'baz' => 'inga',
        ];

        $this->assertSame($expected, $result);
    }

    public function testResolveWithoutMatchingOptions()
    {
        $options = [
            'hello' => 'world',
        ];

        $availableOptions = [
            'foo', 'baz',
        ];

        $result = OptionsResolver::resolve($options, $availableOptions);

        $this->assertSame([], $result);
    }
}
