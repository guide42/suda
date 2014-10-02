<?php

namespace Guide42\Suda;

class Registry
{
    public $settings = array();

    private $services = array();

    public function register($service, $name='') {
        $interfaces = class_implements($service);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Service must implement at least one interface'
            );
        }

        foreach ($interfaces as $interface) {
            if (!array_key_exists($interface, $this->services)) {
                $this->services[$interface] = array();
            }
            $this->services[$interface][$name] = $service;
        }
    }

    public function get($interface, $name='') {
        if (isset($this->services[$interface][$name])) {
            return $this->services[$interface][$name];
        }

        throw new \RuntimeException(
            "Service \"$name\" for $interface not found"
        );
    }

    public function getAll($interface) {
        if (isset($this->services[$interface])) {
            return $this->services[$interface];
        }

        throw new \RuntimeException(
            "Services for $interface not found"
        );
    }
}