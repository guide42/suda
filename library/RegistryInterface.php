<?php

namespace Guide42\Suda;

/**
 * Service container/registry.
 */
interface RegistryInterface extends \Interop\Container\ContainerInterface
{
    /**
     * All dependencies lookup will be delegated to this registry. The default
     * values is the registry itself.
     *
     * @param \Guide42\Suda\RegistryInterface $delegate
     */
    function setDelegateLookupContainer(RegistryInterface $delegate);

    /**
     * Add a service to the container.
     *
     * @param object $service
     * @param string $name
     */
    function register($service, $name='');

    /**
     * Register a closure that will instantiate the service.
     *
     * @param array    $interfaces
     * @param \Closure $factory
     * @param string   $name
     */
    function registerFactory($interfaces, \Closure $factory, $name='');

    /**
     * Define a service to be created when called.
     *
     * @param string $factory
     * @param string $name
     * @param array  $arguments
     */
    function registerDefinition($class, $name='', array $args=array());

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