<?php

namespace Guide42\Suda\Loader;

use Guide42\Suda\RegistryInterface;
use Guide42\Suda\Loader\LoaderInterface;

abstract class Loader implements LoaderInterface
{
    /**
     * @var \Guide42\Suda\RegistryInterface
     */
    private $registry;

    /**
     * @var \Guide42\Suda\Loader\LoaderInterface
     */
    private $delegate;

    public function __construct(RegistryInterface $registry,
                                LoaderInterface $delegate=null) {
        if ($delegate === null) {
            $delegate = $this;
        }

        $this->registry = $registry;
        $this->delegate = $delegate;
    }

    public function getRegistry() {
        return $this->registry;
    }

    public function import($resource) {
        static $loading = array();

        if (isset($loading[$resource])) {
            throw new RuntimeException("Cyclic reference for $resource");
        }

        $loading[$resource] = true;

        $this->delegate->load($resource);

        unset($loading[$resource]);
    }
}