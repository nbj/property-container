<?php

namespace Nbj\Validation;

use Exception;
use InvalidArgumentException;

class PropertyValidationException extends InvalidArgumentException
{
    /**
     * PropertyValidationException constructor.
     *
     * @param string $propertyName
     * @param string|callable $rule
     * @param Exception|null $previous
     */
    public function __construct($propertyName, $rule, $previous = null)
    {
        if (is_callable($rule)) {
            $rule = 'Custom Callable Rule';
        }

        $message = sprintf('[%s] failed validation rule [%s]', $propertyName, $rule);

        parent::__construct($message, 422, $previous);
    }
}