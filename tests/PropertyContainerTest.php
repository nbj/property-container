<?php

use Nbj\PropertyContainer;

class PropertyContainerTest extends PHPUnit_Framework_TestCase
{

    /** @test */
    public function it_contains_properties()
    {
        $container = PropertyContainer::make(array(
            'a_property'       => 'a_value',
            'another_property' => 'another_value'
        ));

        $this->assertInstanceOf('Nbj\PropertyContainer', $container);
        $this->assertEquals('a_value', $container->a_property);
    }
}
