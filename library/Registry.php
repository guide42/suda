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

    function withDelegate(self $delegate) {
        $new = clone $this;
        $new->delegate = $delegate;

        return $new;
    }

    function offsetSet($key, $value) {
        if (!is_string($key)) {
            throw new \TypeError('Entry must be string');
        }

        if (interface_exists($key, false) || class_exists($key, false)) {
            // $this[Car::class] = [V8::class];
            if (is_array($value)) {
                $value = function(self $self, callable $make) use($key, $value) {
                    return $make($key, $value);
                };
            // $this[Engine::class] = V8::class;
            } elseif (is_string($value) && class_exists($value, false)) {
                $value = function(self $self, callable $make) use($value) {
                    return $make($value);
                };
            }

            if (!method_exists($value, '__invoke')) {
                throw new \InvalidArgumentException('Service factory must be callable');
            }

            if (isset($this->factories[$key])) {
                $prev = $this->factories[$key];
                $value = function(self $self, callable $make) use($value, $prev) {
                    return $value($self, function(string $dep=null, array $args=[]) use($prev, $self, $make) {
                        if (is_null($dep) && empty($args)) {
                            return $prev($self, $make);
                        }
                        return $make($dep, $args);
                    });
                };
            }

            $this->factories[$key] = $value;
            $this->keys[$key] = count($this->keys);

            return;
        }

        $this->values[$key] = $value;
        $this->keys[$key] = count($this->keys);
    }

    function offsetGet($key) {
        if (!is_string($key)) {
            throw new \TypeError('Entry must be string');
        }

        if (isset($this->values[$key])) {
            if (method_exists($this->values[$key], '__invoke')) {
                return $this->values[$key]($this);
            }

            return $this->values[$key];
        }

        if (isset($this->factories[$key])) {
            $service = $this->factories[$key]($this, function(string $dep=null, array $args=[]) use($key) {
                // $dep should be a concrete class or null
                // $key could be abstract or interface
                if (is_null($dep) && empty($args)) {
                    return $this->delegate[$key];
                }
                return $this->make($dep, $args);
            });

            if (!$service instanceof $key) {
                throw new \LogicException("Service factory must return an instance of [$key]");
            }

            return $this->values[$key] = $service;
        }

        throw new \RuntimeException("Entry [$key] not found");
    }

    function offsetExists($key) {
        if (!is_string($key)) {
            return false;
        }
        return isset($this->keys[$key]);
    }

    function offsetUnset($key) {
        if (is_string($key)) {
            unset($this->keys[$key], $this->values[$key], $this->factories[$key]);
        }
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

        if (isset($this->loading[$class])) {
            throw new \RuntimeException("Cyclic dependency detected for [$class]");
        }

        $this->loading[$class] = count($this->loading);

        $context = $this->buildContext($constructor->getParameters(), $arguments);
        $service = $reflector->newInstanceArgs($context);

        array_pop($this->loading);

        return $service;
    }

    /** @param array[\ReflectionParameter] $parameters */
    private function buildContext(array $parameters, array $arguments) {
        $parameters = array_filter($parameters, function(\ReflectionParameter $param) {
            return true;
        });

        $context = [];
        $resolve = function($value) {
            if (is_string($value)) {
                if (strncmp($value, '$', 1) === 0) {
                    return $this->delegate[substr($value, 1)];
                }
                if (interface_exists($value, false) || class_exists($value, false)) {
                    return $this->delegate[$value];
                }
            }
            return $value;
        };

        /** @var \ReflectionParameter $param */
        foreach ($parameters as $index => $param) {
            $name = $param->getName();

            if (isset($arguments[$param->getPosition()])) {
                $context[$index] = $resolve($arguments[$param->getPosition()]);
            } elseif (isset($arguments[$name])) {
                $context[$index] = $resolve($arguments[$name]);
            } elseif ($param->hasType() && !$param->getType()->isBuiltin() && isset($this->delegate[strval($param->getType())])) {
                $context[$index] = $this->delegate[strval($param->getType())];
            } elseif ($param->isDefaultValueAvailable()) {
                $context[$index] = $param->getDefaultValue();
            } elseif ($param->isOptional() && !$param->isVariadic()) {
                $context[$index] = null;
            } else {
                throw new \LogicException(sprintf('Parameter [%s] not found for [%s]', $name,
                    key($this->loading)
                ));
            }
        }

        return $context;
    }
}