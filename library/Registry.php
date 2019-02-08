<?php declare(strict_types=1);

namespace suda;

/** Registry of values and dependency injector. */
class Registry implements \ArrayAccess
{
    private $keys = [];
    private $values = [];
    private $loading = [];
    private $factories = [];
    private $delegate;
    private $reflector;

    function __construct(array $values=[], self $delegate=null) {
        $this->delegate = $delegate ?: $this;
        $this->reflector = function($class, string $method) {
            static $cache = [];

            $key = (is_string($class) ? $class : spl_object_hash($class)) .  '::' . $method;

            if (!isset($cache[$key])) {
                $cache[$key] = new \ReflectionMethod($class, $method);
            }

            return $cache[$key];
        };

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /** Assign a new delegate registry. */
    function setDelegate(self $delegate) {
        $this->delegate = $delegate;
    }

    /** Assigns a new reflection maker. */
    function setReflector(callable $reflector) {
        $this->reflector = $reflector;
    }

    /** Assign values and, if key is class or interface, factories. */
    function offsetSet($key, $value): void {
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

    /** Retrieve values or, create and store a service. */
    function offsetGet($key) {
        if (!is_string($key)) {
            throw new \TypeError('Entry must be string');
        }

        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        if (isset($this->factories[$key])) {
            $service = $this->factories[$key]($this->delegate,
                function(string $dep=null, array $args=[]) use($key) {
                    // $dep should be a concrete class or null
                    // $key could be abstract or interface
                    if (is_null($dep) && empty($args)) {
                        return $this->make($key);
                    }
                    // $dep is real dependency and not making $key
                    if ($key !== $dep && isset($this->delegate[$dep])) {
                        return $this->delegate[$dep];
                    }
                    return $this->make($dep, $args);
                }
            );

            if (!$service instanceof $key) {
                throw new \LogicException("Service factory must return an instance of [$key]");
            }

            return $this->values[$key] = $service;
        }

        throw new \RuntimeException("Entry [$key] not found");
    }

    /** Returns true if key exists in registry, false otherwise. */
    function offsetExists($key): bool {
        if (!is_string($key)) {
            return false;
        }
        return isset($this->keys[$key]);
    }

    /** Remove a key from the registry. */
    function offsetUnset($key): void {
        if (is_string($key)) {
            unset($this->keys[$key], $this->values[$key], $this->factories[$key]);
        }
    }

    /** Calls given function with arguments, resolving dependencies. */
    function __invoke($fn, array $args=[]) {
        if (is_string($fn)) {
            if (strpos($fn, '::') !== false) {
                list($class, $method) = explode('::', $fn, 2);
                $instance = $this[$class];
                $reflection = $this->refl($instance, $method);
            } else {
                $instance = $this[$fn];
                $reflection = $this->refl($instance, '__invoke');
            }
        } elseif (method_exists($fn, '__invoke')) {
            $instance = $fn;
            $reflection = $this->refl($instance, '__invoke');
        } elseif (is_array($fn) && isset($fn[0], $fn[1]) && count($fn) === 2) {
            $instance = is_string($fn[0]) ? $this[$fn[0]] : $fn[0];
            $reflection = $this->refl($instance, $fn[1]);
        } else {
            throw new \InvalidArgumentException('Target must be a callable');
        }

        $context = $this->buildContext($this, $reflection->getParameters(), $args);
        $return = $reflection->invokeArgs($instance, $context);

        return $return;
    }

    private function make(string $class, array $args=[]) {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            if (empty($this->loading)) {
                throw new \InvalidArgumentException("Target [$class] cannot be construct");
            }

            throw new \InvalidArgumentException(sprintf("Target [$class] cannot be construct while [%s]",
                implode(', ', array_keys($this->loading))
            ));
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        if (isset($this->loading[$class])) {
            throw new \RuntimeException("Cyclic dependency detected for [$class]");
        }

        $this->loading[$class] = count($this->loading);

        $context = $this->buildContext($this->delegate, $constructor->getParameters(), $args);
        $service = $reflection->newInstanceArgs($context);

        array_pop($this->loading);

        return $service;
    }

    /** @param array[\ReflectionParameter] $params */
    private function buildContext(self $self, array $params, array $args): array {
        $params = array_filter($params, function(\ReflectionParameter $param) {
            return true;
        });

        $context = [];

        /** @var \ReflectionParameter $param */
        foreach ($params as $index => $param) {
            $name = $param->getName();

            if (isset($args[$param->getPosition()])) {
                $context[$index] = $this->resolve($self, $args[$param->getPosition()]);
            } elseif (isset($args[$name])) {
                $context[$index] = $this->resolve($self, $args[$name]);
            } elseif ($param->hasType() && !$param->getType()->isBuiltin() && isset($self[strval($param->getType())])) {
                $context[$index] = $self[strval($param->getType())];
            } elseif ($param->isDefaultValueAvailable()) {
                $context[$index] = $param->getDefaultValue();
            } elseif ($param->isOptional() && !$param->isVariadic()) {
                $context[$index] = null;
            } else {
                if (empty($this->loading)) {
                    throw new \LogicException("Parameter [$name] not found");
                }
                throw new \LogicException(sprintf("Parameter [$name] not found for [%s]",
                    key($this->loading)
                ));
            }
        }

        return $context;
    }

    private function resolve(self $self, $value) {
        if (is_string($value)) {
            if (strncmp($value, '$', 1) === 0) {
                return $self[substr($value, 1)];
            }
            if (interface_exists($value, false) || class_exists($value, false)) {
                return $self[$value];
            }
        }
        return $value;
    }

    private function refl($class, string $method = null) {
        $reflector = $this->reflector;
        $reflection = $reflector($class, $method);

        return $reflection;
    }
}