<?php


namespace Nbj\Validation;


use Nbj\Str;
use Carbon\Carbon;
use InvalidArgumentException;
use Carbon\Exceptions\InvalidFormatException;

class PropertyValidation
{
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
     * @return mixed
     */
    public static function ruleEmail($propertyValue)
    {
        return filter_var($propertyValue, FILTER_VALIDATE_EMAIL);
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