<?php

namespace Nbj;

use BadMethodCallException;
use Doctrine\Common\Inflector\Inflector;

class PropertyContainer
{

    /** @var array $properties Stores all the properties of the instance*/
    protected $properties = array();

    /** @var array $macros Stores all the dynamically created methods*/
    protected static $macros = array();

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
        $container = new static;
        $container->fill($data);

        return $container;
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
        foreach ($data as $property => $value) {
            $this->set($property, $value);
        }

        return $this;
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
        if ($this->has($property)) {
            return $this->properties[$property];
        }

        $method = 'get' . Inflector::camelize($property);
        if (method_exists($this, $method) || array_key_exists($method, self::$macros)) {
            return $this->$method();
        }

        return null;
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
