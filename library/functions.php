<?php declare(strict_types=1);

namespace suda;

function ref(object $object): callable {
    return function(callable $make) use($object) {
        return $object;
    };
}

function build(string $class, array $args=[]): callable {
    return function(callable $make) use($class, $args) {
        return $make($class, $args);
    };
}

function alias(Registry $di, string $key): callable {
    return function(callable $make) use($di, $key) {
        return $di->offsetGet($key);
    };
}
