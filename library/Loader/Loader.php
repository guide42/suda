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

    public function __construct(RegistryInterface $registry) {
        $this->registry = $registry;
    }

    public function getRegistry() {
        return $this->registry;
    }
}