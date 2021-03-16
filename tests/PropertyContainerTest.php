<?php

use Carbon\Carbon;
use Nbj\PropertyContainer;
use PHPUnit\Framework\TestCase;
use Nbj\Validation\PropertyValidationException;

class PropertyContainerTest extends TestCase
{
    /** @test */
    public function it_contains_properties()
    {
        $container = PropertyContainer::make([
            'a_property'       => 'a_value',
            'another_property' => 'another_value'
        ]);

        $this->assertInstanceOf('Nbj\PropertyContainer', $container);
        $this->assertEquals('a_value', $container->a_property);
    }

    /** @test */
    public function it_can_contain_mutator_methods ()
    {
        $example = Example::make([
            'some_required_property' => 'because_its_needed'
        ]);

        $this->assertEquals('value_of_the_mutator', $example->some_mutator);
    }

    /** @test */
    public function mutator_takes_precedence_over_property()
    {
        $container = Example::make([
            'some_required_property' => 'because_its_needed',
            'some_mutator'           => 'some_value'
        ]);

        $this->assertEquals('value_of_the_mutator', $container->some_mutator);
    }

    /** @test */
    public function it_can_contain_macros()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $returnValue = null;

        try {
            $returnValue = $container->thisMethodDoesNotExist();
        } catch (Exception $exception) {
            $this->assertInstanceOf(BadMethodCallException::class, $exception);
            $this->assertEquals('thisMethodDoesNotExist does not exist as a method or a macro on PropertyContainer.', $exception->getMessage());
            $this->assertNull($returnValue);
        }

        PropertyContainer::macro('thisMethodDoesNotExist', function () {
            return 'Now it actually does exist';
        });

        $returnValue = $container->thisMethodDoesNotExist();

        $this->assertEquals('Now it actually does exist', $returnValue);
    }

    /** @test */
    public function it_knows_if_a_macro_method_exists()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $this->assertFalse($container->hasMacro('someMacroMethod'));

        PropertyContainer::macro('someMacroMethod', function () {
            return 'someMacroMethod';
        });

        $this->assertTrue($container->hasMacro('someMacroMethod'));
    }

    /** @test */
    public function it_returns_null_when_a_property_that_does_not_exist_is_been_accessed()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $this->assertNull($container->some_other_property);
    }

    /** @test */
    public function it_knows_if_properties_are_required()
    {
        $container = null;

        try {
            $container = Example::make([
                'some_property' => 'some_value'
            ]);
        } catch (Exception $exception) {
            $this->assertInstanceOf(PropertyValidationException::class, $exception);
            $this->assertEquals('[some_required_property] failed validation rule [required]', $exception->getMessage());
        }

        $this->assertNull($container);
    }

    /** @test */
    public function it_can_forget_properties()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $this->assertEquals('some_value', $container->some_property);

        $container->forget('some_property');

        $this->assertNull($container->some_property);
    }

    /** @test */
    public function it_can_set_new_properties()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $this->assertNull($container->some_other_property);

        $container->some_other_property = 'some_other_value';

        $this->assertEquals('some_other_value', $container->some_other_property);
    }

    /** @test */
    public function it_can_to_convert_it_self_to_an_array()
    {
        $container = PropertyContainer::make([
            'some_property'       => 'some_value',
            'some_other_property' => 'some_other_value'
        ]);

        $this->assertIsArray($container->toArray());
    }

    /** @test */
    public function it_can_to_convert_it_self_to_json()
    {
        $container = PropertyContainer::make([
            'some_property'       => 'some_value',
            'some_other_property' => 'some_other_value'
        ]);

        $this->assertJson($container->toJson());
    }

    /** @test */
    public function it_can_merge_with_other_property_containers()
    {
        $containerA = PropertyContainer::make([
            'some_property' => 'some_value'
        ]);

        $containerB = PropertyContainer::make([
            'some_other_property' => 'some_other_value'
        ]);

        $this->assertTrue($containerA->has('some_property'));
        $this->assertFalse($containerA->has('some_other_property'));

        $containerA->merge($containerB);

        $this->assertTrue($containerA->has('some_property'));
        $this->assertTrue($containerA->has('some_other_property'));
    }

    /** @test */
    public function it_can_auto_convert_properties_designated_as_dates_to_carbon_instances()
    {
        $example = Example::make([
            'some_required_property' => 'is-required-by-out-test-class',
            'test_date'              => '1970-01-01'
        ]);

        $this->assertInstanceOf(Carbon::class, $example->test_date);
    }
}

/**
 * Class Example
 *
 * @property string $some_mutator
 */
class Example extends PropertyContainer
{
    /**
     * Stores the names of required properties. This is mainly used when inheriting from PropertyContainer
     *
     * @var array $validatedProperties
     */
    protected $validatedProperties = [
        'some_required_property' => ['required']
    ];

    /**
     * List of all properties that should be converted to Carbon instances
     *
     * @var array
     */
    protected $dateProperties = [
        'test_date'
    ];

    /**
     * Example mutator method
     *
     * @return string
     */
    public function getSomeMutator()
    {
        return 'value_of_the_mutator';
    }
}
