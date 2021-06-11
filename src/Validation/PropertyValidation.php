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
     * @param $propertyName
     * @param $propertyValue
     */
    public static function validate($rule, $propertyName, $propertyValue)
    {
        $method = 'rule' . Str::toPascal($rule);

        if ( ! method_exists(static::class, $method)) {
            throw new InvalidArgumentException(sprintf('No such rule: %s', $rule));
        }

        if ( ! static::$method($propertyValue)) {
            throw new PropertyValidationException($propertyName, $rule);
        }
    }
}