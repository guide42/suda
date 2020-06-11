<?php declare(strict_types=1);

namespace suda;

/** Registry of values and dependency injector. */
class Registry implements \ArrayAccess
{
    private $keys = [];
    private $frozen = [];
    private $values = [];
    private $loading = [];
    private $factories = [];
    private $delegate;
    private $refl;

    function __construct(array $values=[], self $delegate=null, callable $refl=null) {
        $this->delegate = $delegate ?: $this;
        $this->refl = $refl ?: function($class, string $method=null) {
            static $cache = [];

            if (is_string($class) && class_exists($class, false)) {
                $key = $class;
            } elseif (is_object($class)) {
                $key = get_class($class);
            } else {
                throw new \InvalidArgumentException('Invalid class or class name');
            }
            if ($method !== null) {
                $key .= '::' . $method;
            }

            if (!isset($cache[$key])) {
                if ($method !== null) {
                    $cache[$key] = new \ReflectionMethod($class, $method);
                } else {
                    $cache[$key] = new \ReflectionClass($class);
                }
            }

            return $cache[$key];
        };

        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /** Disallow to assign values or factories, optional, for a specific key. */
    function freeze(string $key=null): int {
        if ($key === null) {
            $this->frozen = true;
        } elseif ($this->frozen !== true) {
            $this->frozen[$key] = true;
            return count($this->frozen);
        }
        return count($this->keys);
    }

    /** Assign values and, if key is class or interface, factories. */
    function offsetSet($key, $value): void {
        if (!is_string($key)) {
            throw new \TypeError('Entry must be string');
        }

        if ($this->frozen === true || isset($this->frozen[$key])) {
            throw new Frozen($key);
        }

        if (interface_exists($key) || class_exists($key)) {
            if (!method_exists($value, '__invoke')) {
                throw new \InvalidArgumentException('Service factory must be callable');
            }

            if (isset($this->factories[$key])) {
                $prev = $this->factories[$key];
                $value = function(callable $make) use($value, $prev) {
                    return $this->__invoke($value, [
                        function(string $dep=null, array $args=[]) use($prev, $make) {
                            if (is_null($dep) && empty($args)) {
                                return $this->__invoke($prev, [$make]);
                            }
                            return $make($dep, $args);
                        },
                    ]);
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

        if ($this->frozen !== true) {
            $this->frozen[$key] = true;
        }

        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        if (isset($this->factories[$key])) {
            $service = $this->__invoke($this->factories[$key], [
                function(string $dep=null, array $args=[]) use($key) {
                    if (is_null($dep) && empty($args)) {
                        return $this->make($key);
                    }
                    return $this->make($dep, $args);
                },
            ]);

            if (!$service instanceof $key) {
                throw new \LogicException("Service factory must return an instance of [$key]");
            }

            return $this->values[$key] = $service;
        }

        throw new NotFound($key);
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
        if ($this->frozen === true) {
            throw new Frozen;
        }
        if (is_string($key)) {
            unset($this->keys[$key], $this->frozen[$key], $this->values[$key], $this->factories[$key]);
        }
    }

    /** Calls given function with arguments, resolving dependencies. */
    function __invoke($fn, array $args=[]) {
        if (is_string($fn)) {
            if (strpos($fn, '::') !== false) {
                list($fn, $method) = explode('::', $fn, 2);
            }
            if ($this->offsetExists($fn)) {
                $instance = $this->offsetGet($fn);
            }
        } elseif (is_array($fn) && isset($fn[0], $fn[1]) && count($fn) === 2) {
            $instance = is_string($fn[0]) ? $this->offsetGet($fn[0]) : $fn[0];
            $method = $fn[1];
        }

        $instance = $instance ?? $fn;
        $method = $method ?? '__invoke';

        if (!method_exists($instance, $method)) {
            throw new \InvalidArgumentException('Target must be a callable');
        }

        $reflection = ($this->refl)($instance, $method);
        $context = $this->buildContext($this->delegate, $reflection->getParameters(), $args);

        if (is_string($instance)) {
            $instance = $this->make($instance);
        }

        return $reflection->invokeArgs($instance, $context);
    }

    /** Creates a concreate class instance initialized with the given arguments. */
    function make(string $class, array $args=[]) {
        $reflection = ($this->refl)($class);

        if (!$reflection->isInstantiable()) {
            if (empty($this->loading)) {
                throw new \UnexpectedValueException("Target [$class] cannot be construct");
            }

            throw new \UnexpectedValueException(sprintf("Target [$class] cannot be construct while [%s]",
                implode(', ', array_keys($this->loading))
            ));
        }

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        if (isset($this->loading[$class])) {
            throw new CyclicDependency($class);
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
            if (isset($args[$param->getPosition()])) {
                $context[$index] = $args[$param->getPosition()];
            } elseif (($name = $param->getName()) && isset($args[$name])) {
                $context[$index] = $args[$name];
            } elseif (($class = $param->getClass() ? $param->getClass()->getName() : null) && $self->offsetExists($class)) {
                $context[$index] = $self->offsetGet($class);
            } elseif ($param->isDefaultValueAvailable()) {
                $context[$index] = $param->getDefaultValue();
            } elseif ($param->allowsNull() || ($param->isOptional() && !$param->isVariadic())) {
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
}