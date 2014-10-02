<?php

namespace Guide42\Suda;

class Registry
{
    public $settings = array();

    private $services = array();

    public function register($service) {
        $interfaces = class_implements($service);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Service must implement at least one interface'
            );
        }

        foreach ($interfaces as $interface) {
            $this->services[$interface] = $service;
        }
    }

    public function get($interface) {
        if (isset($this->services[$interface])) {
            return $this->services[$interface];
        }

        throw new \RuntimeException(
            sprintf('Service for %s not found', $interface)
        );
    }
}