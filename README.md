SUDA
====

Suda is a lightweight container for your services.

API
---

```php
$di = new Registry;                                    // creates empty registry
$di = new Registry(array $values);                     // ... with assoc-array containing values or factories
$di = new Registry(array $values, Registry $delegate); // ... with registry to delegate dependencies

$di[string $classOrInterface] = callable $factory;     // stores a factory for abstract
$di[string $classOrInterface] = string $concreteClass; // ... that will make $concreteClass for $classOrInterface
$di[string $classOrInterface] = array $arguments;      // ... that will make $classOrInterface with given $arguments

$di[string $key] = mixed $value;                       // stores a parameter

$di(callable $fn);                                     // call a function resolving it's parameters
$di(callable $fn, array $arguments);                   // ... with given arguments

$di->freeze();                                         // Disallow to store values or factories
$di->freeze(string $key);                              // ... for this entry key
```

Badges
------

[![Latest Stable Version](https://poser.pugx.org/guide42/suda/v/stable.svg)](https://packagist.org/packages/guide42/suda)
[![Build Status](https://travis-ci.org/guide42/suda.svg?branch=master)](https://travis-ci.org/guide42/suda)
[![Coverage Status](https://coveralls.io/repos/github/guide42/suda/badge.svg?branch=master)](https://coveralls.io/github/guide42/suda)
