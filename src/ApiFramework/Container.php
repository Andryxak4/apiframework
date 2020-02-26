<?php namespace ApiFramework;

/**
 * Container class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Container
{

    /**
     * @var array Container of settings and services
     */
    protected $container = [];


    /**
     * Store a value or service into the container
     *
     * @param string $key Key name
     * @param mixed $value Value or service
     */
    function __set ($id, $value) {
        $this->container[$id] = $value;
    }


    /**
     * Get a value or service from the container
     *
     * @param string $key Key name
     * @param mixed $value Value or service
     */
    function __get ($id) {

        // Abort if the element is not in the container
        if (!isset($this->container[$id])) {
            throw new \InvalidArgumentException($id . ' not defined in container', 500);
        }

        // Return instance
        if (is_callable($this->container[$id])) {
            return $this->container[$id]($this);
        } else {
            return $this->container[$id];
        }
    }


    /**
     * Ensure a value or service will remain globally unique
     *
     * @param string $key Value or object name
     * @param Closure Closure that defines the object
     * @return mixed Instance of the object
     */
    function share ($callable) {
        return function ($c) use ($callable) {
            static $object;
            if (is_null($object)) {
                $object = $callable($c);
            }
            return $object;
        };
    }

}
