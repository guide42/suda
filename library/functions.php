<?php declare(strict_types=1);

namespace suda;

/** Reference of the given object. */
function ref(object $object): callable {
    return function(callable $make) use($object) {
        return $object;
    };
}

/** Alias of given key. */
function alias(Registry $di, string $key): callable {
    return function(callable $make) use($di, $key) {
        return $di->offsetGet($key);
    };
}

/** Creates given class without arguments. */
function invoke(string $class): callable {
    return function(callable $make) use($class) {
        return new $class;
    };
}

/** Creates given class with resolving arguments. */
function build(string $class, array $args=[]): callable {
    return function(callable $make) use($class, $args) {
        return $make($class, $args);
    };
}

/** Creates automatically. */
function automake(): callable {
    return function(callable $make) {
        return $make();
    };
}
