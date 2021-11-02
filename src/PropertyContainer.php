<?php

namespace Nbj;

use Closure;
use Carbon\Carbon;
use BadMethodCallException;
use Nbj\Validation\PropertyValidation;
use Nbj\Validation\PropertyValidationException;

class PropertyContainer
{
    /**
     * Stores all the properties of the instance
     *
     * @var array $properties
     */
    protected $properties = array();

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
     *
     * @deprecated Use getRules() - Will be removed in a future major version
     */
    protected $validatedProperties = array();

    /**
     * List of all properties that should be converted to Carbon instances
     *
     * @var array
     */
    protected $dateProperties = array();

    /**
     * Stores all the dynamically created methods
     *
     * @var array $macros
     */
    protected static $macros = array();

    /**
     * Returns the rules which should be applied to the property container.
     * This is only used when inheriting from PropertyContainer
     *
     * Structure:
     *  [
     *      'property_name' => ['required', 'numeric'],
     *  ]
     * Properties are only validated if present, unless the required validation rule is applied.
     *
     * Override this method to apply validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->validatedProperties;
    }

    /**
     * Adds a closure as a method that is callable on the instance
     *
     * @param string $name The name of the method
     * @param Closure $closure The body of the method
     */
    public static function macro($name, Closure $closure)
    {
        self::$macros[$name] = $closure;
    }

    /**
     * Static construct for PropertyContainer
     *
     * @param array $data
     *
     * @return static
     */
    public static function make(array $data)
    {
        return new static($data);
    }

    /**
     * PropertyContainer constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->fill($data);
    }

    /**
     * Fills an empty PropertyContainer with data
     *
     * @param array $data
     *
     * @return $this
     */
    public function fill(array $data)
    {
        foreach ($this->getRules() as $propertyName => $validationRules) {
            $this->validateProperty($propertyName, $validationRules, $data);
        }

        foreach ($data as $property => $value) {
            $this->set($property, $value);
        }

        return $this;
    }

    /**
     * Runs all validation rules for a property, and throws an exception if any validation rule fails.
     *
     * @param $propertyName
     * @param $validationRules
     * @param $data
     *
     * @throws PropertyValidationException
     */
    protected function validateProperty($propertyName, $validationRules, $data)
    {
        // If the property is not present, or not required then we dont run validation steps
        // or if it is null, and nullable
        if ($this->shouldNotValidateProperty($propertyName, $validationRules, $data)) {
            return;
        }

        // If the property is not present, but required then fail validation
        if ($this->isAMissingAndRequiredProperty($propertyName, $validationRules, $data)) {
            throw new PropertyValidationException($propertyName, 'required');
        }

        // Run all validation rules
        foreach ($validationRules as $validationRule) {
            $this->runValidationRule($validationRule, $propertyName, $data[$propertyName]);
        }
    }

    /**
     * Returns the name and given arguments from the validation rule entry
     *
     * @param $validationRuleEntry
     *
     * @return array
     */
    protected function getNameAndArgumentsFromRule($validationRuleEntry): array
    {
        $splitRule = explode(':', $validationRuleEntry);

        $ruleName = $splitRule[0];
        $ruleArguments = isset($splitRule[1]) ? explode(',', $splitRule[1]) : [];

        return [$ruleName, $ruleArguments];
    }

    /**
     * Runs a single validation rule on a property, and throws and exception if the rule fails
     *
     * @param string|callable $validationRule
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    protected function runValidationRule($validationRule, $propertyName, $propertyValue)
    {
        // The required and nullable validation rules is applied as the first validation rules if present
        // so we can simply skip them here
        if ($validationRule == 'required' || $validationRule == 'nullable') {
            return;
        }

        // If the validation rule is a custom callable, then run it
        // and if it returns false then fail the validation step
        if ( is_callable($validationRule)) {
            if (! $validationRule($propertyValue)) {
                throw new PropertyValidationException($propertyName, $validationRule);
            }

            return;
        }

        [$validationRuleName, $validationRuleArguments] = $this->getNameAndArgumentsFromRule($validationRule);

        // If the validation rule given is a predefined rule
        // Then we run that validation rule
        if (PropertyValidation::hasRule($validationRuleName)) {
            PropertyValidation::validate($validationRuleName, $validationRuleArguments, $propertyName, $propertyValue);
        }
    }

    /**
     * Returns true if the property is both required, and not present in the data array
     *
     * @param string $propertyName
     * @param array $validationRules
     * @param array $data
     *
     * @return bool
     */
    protected function isAMissingAndRequiredProperty($propertyName, $validationRules, $data)
    {
        return $this->propertyIsRequired($validationRules) && ! array_key_exists($propertyName, $data);
    }

    /**
     * Returns true if a property should not be validated.
     *
     * A property should only be validated if it is present, or if it is marked as required
     *
     * @param string $propertyName
     * @param array $validationRules
     * @param array $data
     *
     * @return bool
     */
    protected function shouldNotValidateProperty($propertyName, $validationRules, $data)
    {
        return $this->propertyIsNotPresentAndNotRequired($propertyName, $validationRules, $data)
            || $this->propertyIsNullAndNullable($propertyName, $validationRules, $data);
    }

    /**
     * Returns true if a property is not present, and not required
     *
     * @param string $propertyName
     * @param array $validationRules
     * @param array $data
     *
     * @return bool
     */
    protected function propertyIsNotPresentAndNotRequired($propertyName, $validationRules, $data)
    {
        return ! array_key_exists($propertyName, $data)
            && ! $this->propertyIsRequired($validationRules);
    }

    /**
     * Returns true if a property is null a nullable
     *
     * @param string $propertyName
     * @param array $validationRules
     * @param array $data
     *
     * @return bool
     */
    protected function propertyIsNullAndNullable($propertyName, $validationRules, $data)
    {
        $propertyIsNull = ! isset($data[$propertyName]);

        return $propertyIsNull
            && $this->propertyIsNullable($validationRules);
    }

    /**
     * Returns true if the validated property is required
     *
     * @param $validationRules
     *
     * @return bool
     */
    protected function propertyIsRequired($validationRules)
    {
        return in_array('required', $validationRules);
    }

    /**
     * Returns true if the validated property is required
     *
     * @param $validationRules
     *
     * @return bool
     */
    protected function propertyIsNullable($validationRules)
    {
        return in_array('nullable', $validationRules);
    }

    /**
     * Gets a property
     *
     * @param string $property
     *
     * @return mixed|null
     */
    public function get($property)
    {
        $method = 'get' . Str::toPascal($property);

        if (method_exists($this, $method) || array_key_exists($method, self::$macros)) {
            return $this->$method();
        }

        if ($this->doesNotHave($property)) {
            return null;
        }

        // Checks for date properties and converts them to Carbon instances
        if (in_array($property, $this->dateProperties)) {
            return Carbon::parse($this->properties[$property]);
        }

        return $this->properties[$property];
    }

    /**
     * Sets a property
     *
     * @param string $property
     * @param mixed $value
     *
     * @return $this
     */
    public function set($property, $value)
    {
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     * Checks if a property is set
     *
     * @param string $property
     *
     * @return bool
     */
    public function has($property)
    {
        return isset($this->properties[$property]);
    }

    /**
     * Syntactic sugar for negating has() - Checks if a property is NOT set
     *
     * @param string $property
     *
     * @return bool
     */
    public function doesNotHave($property)
    {
        return ! $this->has($property);
    }

    /**
     * Makes the container forget it ever had a specific property
     *
     * @param string $property
     *
     * @return $this
     */
    public function forget($property)
    {
        if ($this->has($property)) {
            unset($this->properties[$property]);
        }

        return $this;
    }

    /**
     * Checks if a macro exists on the property container
     *
     * @param string $macro
     *
     * @return bool
     */
    public function hasMacro($macro)
    {
        return isset(self::$macros[$macro]);
    }

    /**
     * Merges this PropertyContainer with the properties of another
     * If the PropertyContainers have the same properties, this
     * will be overwritten
     *
     * @param PropertyContainer $container
     *
     * @return PropertyContainer
     */
    public function merge(PropertyContainer $container)
    {
        return $this->fill($container->toArray());
    }

    /**
     * Converts the PropertyContainer to an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->properties;
    }

    /**
     * Convert the PropertyContainer to json
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->properties);
    }

    /**
     * Overload gets
     *
     * @param string $property
     *
     * @return mixed|null
     */
    public function __get($property)
    {
        return $this->get($property);
    }

    /**
     * Overload sets
     *
     * @param string $property
     * @param mixed $value
     *
     * @return $this
     */
    public function __set($property, $value)
    {
        return $this->set($property, $value);
    }

    /**
     * Overload isset
     *
     * @param $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Overload unset
     *
     * @param $name
     */
    public function __unset($name)
    {
        $this->forget($name);
    }

    /**
     * Makes sure macro methods are called if they exist
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        // As of PHP 5.3 it is not possible to use $this inside a closure
        // so we add the instance as the first argument
        $arguments = array_merge(array($this), $arguments);

        if (array_key_exists($method, self::$macros)) {
            return call_user_func_array(self::$macros[$method], $arguments);
        }

        throw new BadMethodCallException(
            sprintf('%s does not exist as a method or a macro on PropertyContainer.', $method)
        );
    }
}
