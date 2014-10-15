<?php

namespace Guide42\Suda;

/**
 * Service container/registry.
 */
interface RegistryInterface
{
    /**
     * Add a service to the container.
     *
     * @param object $service
     * @param string $name
     */
    function register($service, $name='');

    /**
     * Define a service to be created when called.
     *
     * @param string $factory
     * @param string $name
     * @param array  $arguments
     */
    function registerDefinition($factory, $name='', array $arguments=array());

    /**
     * Retrieve service.
     *
     * @param string $interface
     * @param string $name
     * @param array  $context
     *
     * @return object
     */
    function get($interface, $name='', array $context=array());

    /**
     * Retrieve all services by interface.
     *
     * @param string $interface
     *
     * @return array
     */
    function getAll($interface);

    /**
     * Return true if the service is already registered in the container.
     *
     * @param string $interface
     * @param string $name
     *
     * @return boolean
     */
    function has($interface, $name='');
}