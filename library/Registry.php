<?php

namespace Guide42\Suda;

class Registry
{
    public $settings = array();

    private $services = array();
    private $factories = array();

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

    public function registerFactory($factory, array $arguments=array(),
                                    $name='') {
        $interfaces = class_implements($factory);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Factory must implement at least one interface'
            );
        }

        $refl = new \ReflectionClass($factory);

        foreach ($interfaces as $interface) {
            if (!array_key_exists($interface, $this->factories)) {
                $this->factories[$interface] = array();
            }
            $this->factories[$interface][$name] = array($refl, $arguments);
        }
    }

    public function get($interface, $name='', array $context=array()) {
        if (isset($this->services[$interface][$name])) {
            return $this->services[$interface][$name];
        }

        if (isset($this->factories[$interface][$name])) {
            list($factory, $arguments) = $this->factories[$interface][$name];

            foreach ($arguments as $index => $argument) {
                if (isset($context[$index])) {
                    continue;
                }

                if (is_array($argument) && count($argument) > 1) {
                    $context[$index] = $this->get($argument[0], $argument[1]);
                } else {
                    $context[$index] = $this->get($argument);
                }
            }

            $service = $factory->newInstanceArgs($context);

            return $this->services[$interface][$name] = $service;
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