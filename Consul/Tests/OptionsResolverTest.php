<?php

namespace SensioLabs\Consul\Tests;

use PHPUnit\Framework\TestCase;
use SensioLabs\Consul\OptionsResolver;

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
