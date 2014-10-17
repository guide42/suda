<?php

namespace Guide42\Suda;

class Registry implements RegistryInterface
{
    public $settings = array();

    private $services = array();
    private $factories = array();
    private $definitions = array();

    /* This contains the instances of \ReflectionClass that will
     * be used to create new instances of services */
    private $reflcache = array();

    /* Used to detect cyclic dependency, will contain the name of
     * the class being created in the moment as key and a simple
     * true as value */
    private $loading = array();

    public function register($service, $name='') {
        $interfaces = class_implements($service);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Service must implement at least one interface'
            );
        }

        foreach ($interfaces as $interface) {
            $this->services[$interface][$name] = $service;
        }
    }

    public function registerFactory($interfaces, \Closure $factory, $name='') {
        $refl = new \ReflectionFunction($factory);
        $reflParams = $refl->getParameters();

        $params = array();
        foreach ($reflParams as $pos => $relfParam) {
            if ($relfParam->isDefaultValueAvailable()) {
                $params[$pos] = $relfParam->getDefaultValue();
            } elseif (($classHint = $relfParam->getClass()) !== null) {
                $params[$pos] = $classHint->getName();
            }
        }

        foreach ((array) $interfaces as $interface) {
            $this->factories[$interfaces][$name] = array($factory, $params);
        }
    }

    public function registerDefinition($class, $name='', array $args=array()) {
        $interfaces = class_implements($class);

        if (empty($interfaces)) {
            throw new \LogicException(
                'Factory must implement at least one interface'
            );
        }

        foreach ($interfaces as $interface) {
            $this->definitions[$interface][$name] = array($class, $args);
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

                if (is_string($argument) && $this->has($argument)) {
                    $context[$index] = $this->get($argument);
                } else {
                    $context[$index] = $argument;
                }
            }

            $service = call_user_func_array($factory, $context);

            return $this->services[$interface][$name] = $service;
        }

        if (isset($this->definitions[$interface][$name])) {
            list($class, $arguments) = $this->definitions[$interface][$name];

            if (isset($this->loading[$class])) {
                throw new \LogicException(
                    "Cyclic dependency detected for $class"
                );
            }

            $this->loading[$class] = true;

            foreach ($arguments as $index => $argument) {
                if (isset($context[$index])) {
                    continue;
                }

                if (is_string($argument) && $this->has($argument)) {
                    $context[$index] = $this->get($argument);
                } elseif (is_array($argument) && count($argument) === 2 &&
                          $this->has($argument[0], $argument[1])) {
                    $context[$index] = $this->get($argument[0], $argument[1]);
                } else {
                    $context[$index] = $argument;
                }
            }

            if (isset($this->reflcache[$class])) {
                $refl = $this->reflcache[$class];
            } else {
                $refl = $this->reflcache[$class]
                      = new \ReflectionClass($class);
            }

            $service = $refl->newInstanceArgs($context);

            unset($this->loading[$class]);

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

    public function has($interface, $name='') {
        return isset($this->services[$interface][$name]) ||
               isset($this->definitions[$interface][$name]);
    }
}