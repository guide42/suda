<?php

namespace Guide42\Suda\Loader;

/**
 * Permits loading a resource into the Registry.
 */
interface LoaderInterface
{
    /**
     * Retrieve the Registry being loaded.
     *
     * @return \Guide42\Suda\RegistryInterface
     */
    function getRegistry();

    /**
     * Load a resource.
     *
     * @param mixed $resource
     *
     * @throws \LogicException
     */
    function load($resource);

    /**
     * Returns TRUE if the resource is supported by the loader.
     *
     * @param mixed $resource
     *
     * @return boolean
     */
    function supports($resource);
}