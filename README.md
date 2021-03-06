SUDA
====

Suda is a lightweight container for your services.

Quick Start
-----------

Creating and configuring the registry is as simple as giving an associative
array that map a class to a factory or a key to a parameter value.

```php
$di = new suda\Registry([
    MovieFinder::class => function(callable $make) {
        return new MovieFinder;
    },
    MovieLister::class => function(callable $make, MovieFinder $finder) {
        return new MovieLister($finder);
    },
]);
```

All objects are created once, when first requested, using the factory, and the
same instance will be returned at each lookup.

```php
$finder0 = $di[MovieFinder::class];
$finder1 = $di[MovieFinder::class];

assert($finder0 === $finder1);
```

Factories
---------

Any callable that accepts a make function as first argument is a factory, any
other arguments are resolved as dependencies. It purpose is to create the object
when the class or interface is requested.

Factories can be stacked and called in a middleware fashion. The given `make`
function when called without arguments will:

 - Calls the previous factory if stacked,
 - Or tries to create the object of the concrete class key, resolving its
   dependencies.

```php
$di[ConcreteHouse::class] = function(callable $make) {
    $house = $make(); // creates the object resolving dependencies
    return $house;
};
$di[ConcreteHouse::class] = function(callable $make) {
    $house = $make(); // calls the previous factory
    $house->build();
    return $house;
};
```

The `make` function also can be used to create any class with arguments:

```php
$di[NumberValidatorInterface::class] = function(callable $make) {
    return $make(BCNumberValidator::class, ['min' => 5]);
};
```

API
---

```php
$di = new Registry;                                    // creates empty registry
$di = new Registry(array $values);                     // ... with assoc-array containing values or factories
$di = new Registry(array $values, Registry $delegate); // ... with registry to delegate dependencies

$di[string $classOrInterface] = callable $factory;     // stores a factory for abstract
$di[string $key] = mixed $value;                       // stores a parameter

$di(callable $fn);                                     // call a function resolving it's parameters
$di(callable $fn, array $arguments);                   // ... with given arguments

$di->freeze();                                         // disallow to store values or factories
$di->freeze(string $key);                              // ... for this entry key
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/suda/v/stable.svg)](https://packagist.org/packages/guide42/suda)
[![Build Status](https://travis-ci.org/guide42/suda.svg?branch=master)](https://travis-ci.org/guide42/suda)
[![Coverage Status](https://coveralls.io/repos/github/guide42/suda/badge.svg?branch=master)](https://coveralls.io/github/guide42/suda)
