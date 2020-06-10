<?php declare(strict_types=1);

use suda\{Registry, NotFound, Frozen, CyclicDependency};

require_once 'engine.fixture.php';

describe('Registry', function() {
    describe('__construct', function() {
        it('accepts list of values', function() {
            $di = new Registry([
                'param' => 'value',
            ]);

            expect($di->offsetGet('param'))->toBe('value');
        });

        it('accepts a delegate', function() {
            $di = new Registry([
                    Car::class => function(callable $make) {
                        return $make();
                    },
                ],
                new Registry([
                    Engine::class => function(callable $make) {
                        return $make(V8::class);
                    },
                ])
            );

            expect($di->offsetGet(Car::class))->toBeAnInstanceOf(Car::class);
        });

        it('accepts a reflection creator function', function() {
            $di = new Registry([
                    Engine::class => function(callable $make) {
                        return $make(V8::class);
                    },
                ],
                null,
                function($class, string $method=null) {
                    static $first = false;
                    if ($method === null) {
                        // This is the second call.
                        // Here is asking to make the object from within
                        // the constructor factory calling `$make` without
                        // any parameters.
                        expect($class)->toBe('V8');
                        return new ReflectionClass($class);
                    }
                    if (!$first) {
                        // This is the factory defined in the constructor.
                        // Is used to create the V8 object.
                        expect($class)->toBeAnInstanceOf(Closure::class);
                        $first = true;
                    } else {
                        // As last call is the call to the function itself.
                        expect($class)->toBeAnInstanceOf(V8::class);
                    }
                    expect($method)->toBe('__invoke');
                    return new ReflectionMethod($class, '__invoke');
                }
            );
            expect($di('Engine::__invoke', ['prefix' => 'Hello ']))->toBe('Hello World');
        });
    });

    describe('freeze', function() {
        it('freeze a entry', function() {
            $di = new Registry([
                Engine::class => function(callable $make) {
                    return $make(V8::class);
                },
            ]);
            $di->freeze(Engine::class);

            expect(function() use($di) {
                $di[Engine::class] = function() {
                    return new W16(new V8, new V8);
                };
            })
            ->toThrow(new Frozen('Engine'));
        });

        it('freeze all entries', function() {
            $di = new Registry([
                Engine::class => function(callable $make) {
                    return $make(V8::class);
                },
            ]);
            $di->freeze();

            expect(function() use($di) {
                $di[Engine::class] = function() {
                    return new W16(new V8, new V8);
                };
            })
            ->toThrow(new Frozen('Engine'));
        });

        it('does nothing after it has been frozen', function() {
            $di = new Registry;
            $di->freeze();
            $di->freeze('foo');

            expect(function() use($di) {
                unset($di['foo']);
            })
            ->toThrow(new Frozen);

            $di->freeze();
        });

        it('returns the total number of entries', function() {
            $di = new Registry([
                'foo' => 'bar',
                'bar' => 'baz',
            ]);

            expect($di->freeze())->toBe(2);
            expect($di->freeze('foo'))->toBe(2);
        });

        it('returns the number of frozen entries', function() {
            $di = new Registry([
                'foo' => 'bar',
                'bar' => 'baz',
                'baz' => 'foo',
            ]);

            expect($di->freeze('foo'))->toBe(1);
            expect($di->freeze('bar'))->toBe(2);
            expect($di->freeze('baz'))->toBe(3);
        });
    });

    describe('offsetSet', function() {
        it('assigns parameters', function() {
            $di = new Registry;
            $di->offsetSet('param0', 'value0');
            $di->offsetSet('param1', 'value1');

            expect($di->offsetGet('param0'))->toBe('value0');
            expect($di->offsetGet('param1'))->toBe('value1');
        });

        it('assigns callables as factories', function() {
            $di = new Registry;
            $di->offsetSet(Car::class, function() {
                return new Car(new V8);
            });

            expect($di->offsetGet(Car::class))->toBeAnInstanceOf(Car::class);
        });

        it('assigns parameters for key concrete class', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet(Car::class, ['color' => 'blue']);

            expect($di->offsetGet(Car::class)->color)->toBe('blue');
        });

        it('assigns parameters for key concrete class that are resolver from delegate', function() {
            $di = new Registry;
            $di->offsetSet('color', 'blue');
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet(Car::class, ['color' => '$color']);

            expect($di->offsetGet(Car::class)->color)->toBe('blue');
        });

        it('assigns concrete classes as aliases', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, V8::class);

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(V8::class);
        });

        it('assigns stacked factories, next is make function called without arguments', function() {
            $v8 = null;
            $count = 0;

            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) use(&$v8, &$count) {
                $v8 = new V8;
                $count++;

                return $v8;
            });
            $di->offsetSet(Engine::class, function(callable $make) use(&$v8, &$count) {
                $service = $make();
                $count++;

                expect($service)->toBe($v8);

                return new V8;
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(V8::class)->not->toBe($v8);
            expect($count)->toBe(2);
        });

        it('assigns last factory', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet(Engine::class, function() {
                return new W16(new V8, new V8);
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(W16::class);
        });

        it('throws InvalidArgumentException when is not class nor callable', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetSet(V8::class, 123);
            })
            ->toThrow(new InvalidArgumentException('Service factory must be callable'));
        });

        it('throws Frozen when entry is frozen', function() {
            $di = new Registry([
                Engine::class => function(callable $make) {
                    return $make(V8::class);
                },
            ]);

            $v8 = $di->offsetGet(Engine::class);

            expect($v8)->toBeAnInstanceOf(V8::class);

            expect(function() use($di) {
                $di->offsetSet(Engine::class, function() {
                    return new W16(new V8, new V8);
                });
            })
            ->toThrow(new Frozen('Engine'));
        });

        it('throws TypeError when key is not string', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetSet(new stdClass, 'value');
            })
            ->toThrow(new TypeError('Entry must be string'));
        });
    });

    describe('offsetGet', function() {
        it('calls factory only the first time', function() {
            $count = 0;
            $service = null;

            $di = new Registry;
            $di->offsetSet(Engine::class, function() use(&$count, &$service) {
                $count++;
                $service = new V8;

                return $service;
            });

            expect($di->offsetGet(Engine::class))->toBe($service);
            expect($di->offsetGet(Engine::class))->toBe($service);

            expect($count)->toBe(1);
        });

        it('calls factory with make function', function() {
            $delegate = new Registry;
            $di = new Registry([], $delegate);
            $di->offsetSet(Engine::class, function(callable $make) use($delegate) {
                expect($make)->toBeAnInstanceOf(Closure::class);

                return new V8;
            });
            $di->offsetGet(Engine::class);
        });

        it('calls factory with make function that creates an instance with dependencies from deleagte', function() {
            $delegate = new Registry;
            $delegate->offsetSet('leftEngine', new V8);
            $delegate->offsetSet('rightEngine', new V8);

            $di = new Registry([], $delegate);
            $di->offsetSet('leftEngine', new W16(new V8, new V8));
            $di->offsetSet('rightEngine', new W16(new V8, new V8));
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(W16::class, [
                    'left' => '$leftEngine',
                    'right' => '$rightEngine',
                ]);
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(W16::class);
            expect($di->offsetGet(Engine::class)->left)->toBeAnInstanceOf(V8::class);
        });

        it('calls factory with make function that creates an instance and resolve it\'s parameters from deleagte', function() {
            $delegate = new Registry;
            $delegate->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            $di = new Registry([], $delegate);
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(W16::class);
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(W16::class);
            expect($di->offsetGet(Engine::class)->left)->toBeAnInstanceOf(V8::class);
            expect($di->offsetGet(Engine::class)->right)->toBeAnInstanceOf(V8::class);
        });

        it('calls factory with make function that when called without parameters it creates an instance of the abstract', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, function(callable $make) {
                return $make();
            });

            expect($di->offsetGet(V8::class))->toBeAnInstanceOf(V8::class);
        });

        it('throws LogicException when factory does not return an instance of the abstract', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function() {
                return new Car(new V8);
            });

            expect(function() use($di) {
                $di->offsetGet(Engine::class);
            })
            ->toThrow(new LogicException('Service factory must return an instance of [Engine]'));
        });

        it('throws NotFound when entry not found', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetGet('not_found');
            })
            ->toThrow(new NotFound('not_found'));
        });

        it('throw TypeError when key is not string', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetGet(new stdClass);
            })
            ->toThrow(new TypeError('Entry must be string'));
        });
    });

    describe('offsetExists', function() {
        it('returns true if parameter exists', function() {
            $di = new Registry;
            $di->offsetSet('param', 'value');

            expect($di->offsetExists('param'))->toBe(true);
        });

        it('returns true if factory exists', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            expect($di->offsetExists(Engine::class))->toBe(true);
            expect($di->offsetExists(V8::class))->toBe(false);
        });

        it('returns false if key not found', function() {
            expect((new Registry)->offsetExists('not_found'))->toBe(false);
        });

        it('returns false if key is not string', function() {
            expect((new Registry)->offsetExists(new stdClass))->toBe(false);
        });
    });

    describe('offsetUnset', function() {
        it('throws Frozen when Registry is frozen', function() {
            $di = new Registry;
            $di->freeze();

            expect(function() use($di) {
                unset($di['foo']);
            })
            ->toThrow(new Frozen);
        });

        it('removes a parameter/factory', function() {
            $di = new Registry;
            $di->offsetSet('param', 'value');
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            expect($di->offsetExists('param'))->toBe(true);
            expect($di->offsetExists(Engine::class))->toBe(true);

            $di->offsetUnset('param');
            $di->offsetUnset(Engine::class);

            expect($di->offsetExists('param'))->toBe(false);
            expect($di->offsetExists(Engine::class))->toBe(false);
        });

        it('does nothing if key is not string', function() {
            $di = new Registry;
            $key = new stdClass;

            $di->offsetUnset($key);

            expect(function() use($di, $key) {
                $di->offsetGet($key);
            })
            ->toThrow(new TypeError('Entry must be string'));
        });
    });

    describe('__invoke', function() {
        it('calls callable when object that has __invoke method is given', function() {
            expect((new Registry)->__invoke(new V8, ['prefix' => '']))->toBe('World');
        });

        it('calls callable when class that is not in container and has __invoke method is given', function() {
            expect((new Registry)->__invoke(V8::class, ['prefix' => '']))->toBe('World');
        });

        it('calls callable when class that is in container and has __invoke method is given', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            expect($di->__invoke(Engine::class))->toBe('World');
        });

        it('calls callable when class that is in container and method split by :: is given', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            expect($di->__invoke(Engine::class . '::__invoke'))->toBe('World');
        });

        it('calls callable when array of object and method is given', function() {
            expect((new Registry)->__invoke([new V8, '__invoke'], ['prefix' => '']))->toBe('World');
        });

        it('calls callable when array of class that is in container and method is given', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });

            expect($di->__invoke([Engine::class, '__invoke']))->toBe('World');
        });

        it('calls callable when string of closure that is in the container is given', function() {
            $di = new Registry;
            $di['my_func'] = function($val) {
                return $val;
            };

            expect($di->__invoke('my_func', [42]))->toBe(42);
        });

        it('calls callable resolving parameters by class and name', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class . '$v8', function(callable $make) {
                return $make(V8::class);
            });

            $v8 = $di->__invoke(function(Engine $v8) {
                return $v8;
            });

            expect($v8)->toBeAnInstanceOf(V8::class);
        });

        it('calls callable resolving parameters by type hint', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, function(callable $make) {
                return $make();
            });

            $v8 = $di->__invoke(function(V8 $v8) {
                return $v8;
            });

            expect($v8)->toBeAnInstanceOf(V8::class);
        });

        it('calls callable resolving parameters from delegate', function() {
            $v80 = new V8;
            $v81 = new V8;

            $delegate = new Registry;
            $delegate[Car::class] = function(callable $make) use($v80) {
                return $make(Car::class, [$v80]);
            };

            $di = new Registry([], $delegate);
            $di[Car::class] = function(callable $make) use($v81) {
                return $make(Car::class, [$v81]);
            };

            $di(function(Car $car) use($v80) {
                expect($car->engine)->toBe($v80);
            });
        });

        it('calls callable resolving arguments by type hint', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, function(callable $make) {
                return $make();
            });

            $fn = function($v8) {
                return $v8;
            };

            expect($di->__invoke($fn, [V8::class]))->toBeAnInstanceOf(V8::class);
        });

        it('calls callable resolving arguments prefixed with dollar sign', function() {
            $di = new Registry;
            $di->offsetSet('color', 'blue');

            $fn = function($c) {
                return $c;
            };

            expect($di->__invoke($fn, ['$color']))->toBe('blue');
        });

        it('calls callable resolving arguments from delegate', function() {
            $delegate = new Registry;
            $delegate->offsetSet('color', 'green');

            $di = new Registry([], $delegate);
            $di->offsetSet('color', 'blue');

            $fn = function($c) {
                return $c;
            };

            expect($di->__invoke($fn, ['$color']))->toBe('green');
        });

        it('throws InvalidArgumentException when target is not callable', function() {
            expect(function() {
                (new Registry)->__invoke(new stdClass);
            })
            ->toThrow(new InvalidArgumentException('Target must be a callable'));
        });

        it('throws LogicException when parameter not found', function() {
            expect(function() {
                (new Registry)->__invoke(function(V8 $v8) {});
            })
            ->toThrow(new LogicException('Parameter [v8] not found'));
        });
    });

    describe('make', function() {
        it('creates a concrete class without constructor', function() {
            expect((new Registry)->make(V8::class))->toBeAnInstanceOf(V8::class);
        });

        it('creates a concrete class with given arguments from position', function() {
            $di = new Registry;
            $v8 = $di->make(V8::class);

            expect($di->make(W16::class, [$v8, $v8]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class by resolving given arguments from position', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, function(callable $make) {
                return $make();
            });

            expect($di->make(W16::class, [V8::class, V8::class]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class with given arguments from name', function() {
            $di = new Registry;
            $v8 = new V8;

            expect($di->make(W16::class, ['left' => $v8, 'right' => $v8]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class resolving dependency by class in the argument', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, function(callable $make) {
                return $make();
            });

            expect($di->make(W16::class, ['left' => V8::class, 'right' => V8::class]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class resolving dependency by param in the argument prefixed with dollar sign', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet('color', 'blue');

            expect($di->make(Car::class, ['color' => '$color'])->color)->toBe('blue');
        });

        it('creates a concrete class with delegate lookup the parameter type hint', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet(W16::class, function(callable $make) {
                return $make();
            });

            expect($di->make(W16::class))->toBeAnInstanceOf(W16::class);
        });

        // require pdo-sqlite
        xit('creates a concrete class with no default values but optional', function() {
            $di = new Registry;
            $di->offsetSet(PDO::class, ['sqlite://:memory:']);

            allow(PDO::class)->toBeOk();
            expect($di->make(PDO::class))->toBeAnInstanceOf(PDO::class);
        });

        it('creates a concrete class with the parameter default value', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(V8::class);
            });
            $di->offsetSet(Car::class, function(callable $make) {
                return $make();
            });

            $car = $di->make(Car::class);

            expect($car)->toBeAnInstanceOf(Car::class);
            expect($car->color)->toBe('red');
        });

        it('throws InvalidArgumentException when given class is an interface', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(Engine::class);
            });

            expect(function() use($di) {
                $di->make(Engine::class);
            })
            ->toThrow(new InvalidArgumentException('Invalid class or class name'));
        });

        it('throws UnexpectedValueException when given class is abstract', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(BaseEngine::class);
            });

            expect(function() use($di) {
                $di->make(BaseEngine::class);
            })
            ->toThrow(new UnexpectedValueException('Target [BaseEngine] cannot be construct'));
        });

        it('throws UnexpectedValueException when resolved class is abstract', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(BaseEngine::class);
            });
            $di->offsetSet(Car::class, function(callable $make) {
                return $make();
            });

            expect(function() use($di) {
                $di->make(Car::class);
            })
            ->toThrow(new UnexpectedValueException('Target [BaseEngine] cannot be construct while [Car]'));
        });

        it('throws CyclicDependency when a cyclic dependency is detected', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function(callable $make) {
                return $make(W16::class);
            });
            $di->offsetSet(W16::class, function(callable $make) {
                return $make();
            });

            expect(function() use($di) {
                $di->make(W16::class);
            })
            ->toThrow(new CyclicDependency('W16'));
        });

        it('throws LogicException when a parameter is not found', function() {
            $di = new Registry;
            $di->offsetSet(Car::class, function(callable $make) {
                return $make();
            });

            expect(function() use($di) {
                $di->make(Car::class);
            })
            ->toThrow(new LogicException('Parameter [engine] not found for [Car]'));
        });

        it('throws InvalidArgumentException when no class is given', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->make('foobar');
            })
            ->toThrow(new InvalidArgumentException('Invalid class or class name'));
        });
    });
});