<?php


namespace Nbj\Validation;


use Nbj\Str;
use Carbon\Carbon;
use InvalidArgumentException;
use Carbon\Exceptions\InvalidFormatException;

class PropertyValidation
{
    /**
     * Validate that an attribute is greater than another attribute.
     *
     * @param  mixed  $propertyValue
     * @param  mixed  $arguments
     * @return bool
     */
    public static function ruleGreaterThan($propertyValue, $arguments)
    {
        $comparedToValue = $arguments[0];

        if (is_numeric($propertyValue) and is_numeric($comparedToValue)) {
            return $propertyValue > $comparedToValue;
        }

        return false;
    }

    /**
     * Validate that an attribute is equal or greater than another attribute.
     *
     * @param  mixed  $propertyValue
     * @param  mixed  $arguments
     * @return bool
     */
    public static function ruleGreaterThanEqual($propertyValue, $arguments)
    {
        $comparedToValue = $arguments[0];

        if (is_numeric($propertyValue) and is_numeric($comparedToValue)) {
            return $propertyValue >= $comparedToValue;
        }

        return false;
    }

    /**
     * Validate that an attribute is less than another attribute.
     *
     * @param  mixed  $propertyValue
     * @param  mixed  $arguments
     * @return bool
     */
    public static function ruleLessThan($propertyValue, $arguments)
    {
        $comparedToValue = $arguments[0];

        if (is_numeric($propertyValue) and is_numeric($comparedToValue)) {
            return $propertyValue < $comparedToValue;
        }

        return false;
    }

    /**
     * Validate that an attribute is less than or equal to another attribute.
     *
     * @param  mixed  $propertyValue
     * @param  mixed  $arguments
     * @return bool
     */
    public static function ruleLessThanEqual($propertyValue, $arguments)
    {
        $comparedToValue = $arguments[0];

        if (is_numeric($propertyValue) and is_numeric($comparedToValue)) {
            return $propertyValue <= $comparedToValue;
        }

        return false;
    }

    /**
     * The property must be numeric
     *
     * @param mixed $propertyValue
     *
     * @return bool
     */
    public static function ruleNumeric($propertyValue)
    {
        if (is_numeric($propertyValue)) {
            return true;
        }

        return false;
    }

    /**
     * The property must be an uuid
     *
     * @param $propertyValue
     *
     * @return bool
     */
    public static function ruleUuid($propertyValue)
    {
        if (! is_string($propertyValue)) {
            return false;
        }

        return (bool) preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $propertyValue);
    }

    /**
     * The property must be integer
     *
     * @param mixed $propertyValue
     *
     * @return bool
     */
    public static function ruleInt($propertyValue)
    {
        if (is_int($propertyValue)) {
            return true;
        }

        if (((int) $propertyValue) == $propertyValue) {
            return true;
        }

        return false;
    }

    /**
     * The property must not be null
     *
     * @param mixed $propertyValue
     *
     * @return bool
     */
    public static function ruleNotNull($propertyValue)
    {
        if (is_null($propertyValue)) {
            return false;
        }

        return true;
    }

    /**
     * The property must be a date (Parsable by Carbon)
     *
     * @param mixed $propertyValue
     *
     * @return bool
     */
    public static function ruleDate($propertyValue)
    {
        try {
            Carbon::parse($propertyValue);
        } catch (InvalidFormatException $exception) {
            return false;
        }

        return true;
    }

    /**
     * The property must be a date (Parsable by Carbon) and have a correct format
     *
     * @param mixed $propertyValue
     * @param array $arguments
     *
     * @return bool
     */
    public static function ruleDateFormat($propertyValue, $arguments)
    {
        try {
            $date = Carbon::parse($propertyValue);

            return $date->format($arguments[0]) == $propertyValue;
        } catch (InvalidFormatException $exception) {
            return false;
        }
    }

    /**
     * The property must be a string
     *
     * @param mixed $propertyValue
     *
     * @return bool
     */
    public static function ruleString($propertyValue)
    {
        return is_string($propertyValue);
    }

    /**
     * The property must be an email
     *
     * @param $propertyValue
     *
     * @return bool
     */
    public static function ruleEmail($propertyValue)
    {
        return (bool) filter_var($propertyValue, FILTER_VALIDATE_EMAIL);
    }

    /**
     * The property must exist within the arguments
     *
     * @param mixed $propertyValue
     * @param array $arguments
     *
     * @return bool
     */
    public static function ruleIn($propertyValue, $arguments)
    {
        return in_array($propertyValue, $arguments, false);
    }

    /**
     * Runs a validation rule on a property, and throws an exception if the validation failed.
     *
     * @param $rule
     * @param $arguments
     * @param $propertyName
     * @param $propertyValue
     */
    public static function validate($rule, $arguments, $propertyName, $propertyValue)
    {
        if ( ! static::hasRule($rule)) {
            throw new InvalidArgumentException(sprintf('No such rule: %s', $rule));
        }

        $method = static::getRuleMethod($rule);

        if ( ! static::$method($propertyValue, $arguments)) {
            throw new PropertyValidationException($propertyName, $rule);
        }
    }

    /**
     * Returns true if the rule exists
     *
     * @param $rule
     *
     * @return bool
     */
    public static function hasRule($rule): bool
    {
        return method_exists(static::class, static::getRuleMethod($rule));
    }

    /**
     * Returns the associated rule method name
     *
     * @param $rule
     *
     * @return string
     */
    protected static function getRuleMethod($rule): string
    {
        return 'rule' . Str::toPascal($rule);
    }
}
