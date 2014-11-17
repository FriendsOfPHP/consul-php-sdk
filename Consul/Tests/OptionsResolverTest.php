<?php

namespace SensioLabs\Consul\Tests;

use SensioLabs\Consul\OptionsResolver;

class OptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolve()
    {
        $options = array(
            'foo' => 'bar',
            'hello' => 'world',
            'baz' => 'inga',
        );

        $availableOptions = array(
            'foo', 'baz'
        );

        $result = OptionsResolver::resolve($options, $availableOptions);

        $expected = array(
            'foo' => 'bar',
            'baz' => 'inga',
        );

        $this->assertSame($expected, $result);
    }

    public function testResolveWithoutMatchingOptions()
    {
        $options = array(
            'hello' => 'world',
        );

        $availableOptions = array(
            'foo', 'baz'
        );

        $result = OptionsResolver::resolve($options, $availableOptions);

        $this->assertSame(array(), $result);
    }
}
