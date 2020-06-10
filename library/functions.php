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

/** Creates automatically. */
function automake() {
    return function(callable $make) {
        return $make();
    };
}

/** Creates given class with arguments. */
function build(string $class, array $args=[]): callable {
    return function(callable $make) use($class, $args) {
        return $make($class, $args);
    };
}
