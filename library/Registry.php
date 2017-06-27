<?php declare(strict_types=1);

namespace suda;

class Registry implements \ArrayAccess
{
    private $keys = array();
    private $values = array();
    private $loading = array();
    private $factories = array();
    private $delegate;

    function __construct(array $values = array(), self $delegate=null) {
        $this->delegate = $delegate ?: $this;

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    function offsetSet($key, $value) {
        if (!interface_exists($key, false) && !class_exists($key, false)) {
            $this->values[$key] = $value;
            $this->keys[$key] = count($this->keys);
        } else {
            if (is_string($value) && class_exists($value, false)) {
                $value = function(self $self, callable $make) use($value) {
                    return $make($value);
                };
            }

            if (!method_exists($value, '__invoke')) {
                throw new \InvalidArgumentException('Service factory must be callable');
            }

            /*if (isset($this->factories[$key])) {
                $prev = $this->factories[$key];
                $value = function(self $self, callable $make) use($value, $prev) {
                    return $value($self, function(string $dep=null, array $args=[]) use($prev, $self, $make) {
                        if (is_null($dep)) {
                            return $prev($self, $make);
                        }
                        return $make($dep, $args);
                    });
                };
            }*/

            $this->factories[$key] = $value;
            $this->keys[$key] = count($this->keys);
        }
    }

    function offsetGet($key) {
        if (isset($this->values[$key])) {
            if (method_exists($this->values[$key], '__invoke')) {
                return $this->values[$key]($this);
            }

            return $this->values[$key];
        }

        if (isset($this->factories[$key])) {
            $service = $this->factories[$key]($this, function(string $dep=null, array $args=[]) use($key) {
                if (is_null($dep)) {
                    return $this->delegate[$key];
                }
                return $this->delegate->make($dep, $args);
            });

            if (!$service instanceof $key) {
                throw new \LogicException("Service factory must return an instance of [$key]");
            }

            return $this->values[$key] = $service;
        }

        throw new \RuntimeException("Entry [$key] not found");
    }

    function offsetExists($key) {
        return isset($this->keys[$key]);
    }

    function offsetUnset($key) {
        unset($this->keys[$key], $this->values[$key], $this->factories[$key]);
    }

    function make(string $class, array $arguments=[]) {
        $reflector = new \ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            if (empty($this->loading)) {
                throw new \InvalidArgumentException("Target [$class] cannot be construct");
            }

            throw new \InvalidArgumentException(sprintf("Target [$class] cannot be construct while [%s]",
                implode(', ', array_keys($this->loading))
            ));
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        $this->loading[$class] = count($this->loading);

        $context = $this->buildContext($constructor->getParameters(), $arguments);
        $service = $reflector->newInstanceArgs($context);

        array_pop($this->loading);

        return $service;
    }

    private function buildContext(array $parameters, array $arguments) {
        $parameters = array_filter($parameters, function(\ReflectionParameter $param) { return true; });
        $context = [];

        /** @var \ReflectionParameter $param */
        foreach ($parameters as $index => $param) {
            if (isset($arguments[$param->getPosition()])) {
                $context[$index] = $arguments[$param->getPosition()];
            } elseif (isset($arguments[$param->getName()])) {
                $context[$index] = $arguments[$param->getName()];
            } elseif ($param->hasType() && !$param->getType()->isBuiltin() && isset($this->delegate[strval($param->getType())])) {
                $context[$index] = $this->delegate[strval($param->getType())];
            } elseif (isset($this->delegate[$param->getName()])) {
                $context[$index] = $this->delegate[$param->getName()];
            } elseif ($param->isDefaultValueAvailable()) {
                $context[$index] = $param->getDefaultValue();
            } else {
                throw new \LogicException(sprintf('Parameter [%s] not found', $param->getName()));
            }
        }

        return $context;
    }
}