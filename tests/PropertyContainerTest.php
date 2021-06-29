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
            'another_property' => 'another_value',
        ]);

        $this->assertInstanceOf('Nbj\PropertyContainer', $container);
        $this->assertEquals('a_value', $container->a_property);
    }

    /** @test */
    public function it_can_contain_mutator_methods()
    {
        $example = Example::make([
            'some_required_property' => 'because_its_needed',
        ]);

        $this->assertEquals('value_of_the_mutator', $example->some_mutator);
    }

    /** @test */
    public function mutator_takes_precedence_over_property()
    {
        $container = Example::make([
            'some_required_property' => 'because_its_needed',
            'some_mutator'           => 'some_value',
        ]);

        $this->assertEquals('value_of_the_mutator', $container->some_mutator);
    }

    /** @test */
    public function it_can_contain_macros()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value',
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
            'some_property' => 'some_value',
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
            'some_property' => 'some_value',
        ]);

        $this->assertNull($container->some_other_property);
    }

    /** @test */
    public function it_knows_if_properties_are_required()
    {
        $container = null;

        try {
            $container = Example::make([
                'some_property' => 'some_value',
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
            'some_property' => 'some_value',
        ]);

        $this->assertEquals('some_value', $container->some_property);

        $container->forget('some_property');

        $this->assertNull($container->some_property);
    }

    /** @test */
    public function it_can_set_new_properties()
    {
        $container = PropertyContainer::make([
            'some_property' => 'some_value',
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
            'some_other_property' => 'some_other_value',
        ]);

        $this->assertIsArray($container->toArray());
    }

    /** @test */
    public function it_can_to_convert_it_self_to_json()
    {
        $container = PropertyContainer::make([
            'some_property'       => 'some_value',
            'some_other_property' => 'some_other_value',
        ]);

        $this->assertJson($container->toJson());
    }

    /** @test */
    public function it_can_merge_with_other_property_containers()
    {
        $containerA = PropertyContainer::make([
            'some_property' => 'some_value',
        ]);

        $containerB = PropertyContainer::make([
            'some_other_property' => 'some_other_value',
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
            'test_date'              => '1970-01-01',
        ]);

        $this->assertInstanceOf(Carbon::class, $example->test_date);
    }

    /** @test */
    public function it_can_successfully_validate_fields()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_not_null_property' => 0,
            'some_integer_property'  => 100,
            'some_numeric_property'  => '12.52',
            'some_date_property'     => '2021-01-01 01:00:00',
        ]);

        // Assert
        $this->assertTrue(true); // No exceptions were thrown
    }

    /** @test */
    public function it_can_fail_null_validation()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_not_null_property' => null,
        ]);
    }

    /** @test */
    public function it_can_fail_integer_property_validation()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_integer_property'  => 125.25,
        ]);
    }

    /** @test */
    public function it_can_fail_numeric_property_validation()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_numeric_property'  => ['123', "abc"],
        ]);
    }

    /** @test */
    public function it_can_fail_date_property_validation()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_numeric_property'  => 'Not a date',
        ]);
    }

    /** @test */
    public function it_accepts_null_for_a_nullable_required_string_property()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property'                 => 'some random value',
            'some_required_nullable_string_property' => null,
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_accepts_no_value_for_a_nullable_required_string_property()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property' => 'some random value',
            //            'some_required_nullable_string_property' => null,
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_accept_a_valid_email()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_email_property'    => 'testing@email.com',
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_accept_an_invalid_email()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_email_property'    => 'testingemail.com',
        ]);
    }

    /** @test */
    public function it_accepts_a_string_for_a_nullable_required_string_property()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property'                 => 'some random value',
            'some_required_nullable_string_property' => 'some string',
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_accept_an_int_for_a_nullable_required_string_property()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property'                 => 'some random value',
            'some_required_nullable_string_property' => 123,
        ]);
    }

    /** @test */
    public function it_accepts_a_valid_date_formatted_string()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property'    => 'some random value',
            'some_date_format_property' => '2021-10-01',
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_accepts_an_invalid_date_formatted_string()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property'    => 'some random value',
            'some_date_format_property' => '01-10-2021',
        ]);
    }

    /** @test */
    public function it_accepts_a_valid_in_strings_property()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_in_rule_strings'   => 'a',
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_accepts_an_invalid_in_strings_property()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_in_rule_strings'   => 'd',
        ]);
    }

    /** @test */
    public function it_accepts_a_valid_in_int_property()
    {
        // Arrange

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_in_rule_int'       => 1,
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /** @test */
    public function it_does_not_accepts_an_invalid_in_int_property()
    {
        // Arrange
        $this->expectException(PropertyValidationException::class);

        // Act
        new Example([
            'some_required_property' => 'some random value',
            'some_in_rule_int'       => 4,
        ]);
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
     * Stores a list of properties, and validation rules to apply - This is only used when inheriting from PropertyContainer
     *
     * Structure:
     *  [
     *      'property_name' => ['required', 'numeric'],
     *  ]
     *
     * Properties are only validated if present, unless the required validation rule is applied.
     *
     * @see PropertyValidation for a list of validation rules
     *
     * @var array $validatedProperties
     */
    protected $validatedProperties = [
        'some_required_property'                 => ['required'],
        'some_not_null_property'                 => ['notNull'],
        'some_integer_property'                  => ['int'],
        'some_numeric_property'                  => ['numeric'],
        'some_date_property'                     => ['date'],
        'some_date_format_property'              => ['date_format:Y-m-d'],
        'some_required_nullable_string_property' => ['required', 'nullable', 'string'],
        'some_email_property'                    => ['email'],
        'some_in_rule_strings'                   => ['in:a,b,c'],
        'some_in_rule_int'                       => ['in:1,2,3'],
    ];

    /**
     * List of all properties that should be converted to Carbon instances
     *
     * @var array
     */
    protected $dateProperties = [
        'test_date',
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
