<?php declare(strict_types=1);

use suda\Registry;

interface Engine {}

class V8 implements Engine {}

class W16 implements Engine {
    public $left;
    public $right;

    function __construct(Engine $left, Engine $right) {
        $this->left = $left;
        $this->right = $right;
    }
}

class Car {
    public $engine;
    public $color;

    function __construct(Engine $engine, string $color='red') {
        $this->engine = $engine;
        $this->color = $color;
    }
}

describe('Registry', function() {
    describe('__construct', function() {
        it('accepts list of values', function() {
            $di = new Registry([
                'param' => 'value',
            ]);

            expect($di->offsetGet('param'))->toBe('value');
        });

        it('accepts a delegate', function() {
            $di = new Registry([Car::class => Car::class],
                new Registry([Engine::class => V8::class]));

            expect($di->offsetGet(Car::class))->toBeAnInstanceOf(Car::class);
        });
    });

    describe('withDelegate', function() {
        it('returns a clone with new delegate', function() {
            $delegate0 = new Registry([Engine::class => V8::class]);
            $delegate1 = new Registry([Engine::class => function() {
                return new W16(new V8, new V8);
            }]);

            $di0 = new Registry;
            $di1 = $di0->withDelegate($delegate0);
            $di2 = $di1->withDelegate($delegate1);

            expect($di0)->not->toBe($di1)->not->toBe($di2);
            expect($di1)->not->toBe($di2);

            expect(function() use($di0) {
                $di0->offsetGet(Engine::class);
            })
            ->toThrow(new RuntimeException('Entry [Engine] not found'));

            expect($di1->make(Car::class)->engine)->toBeAnInstanceOf(V8::class);
            expect($di2->make(Car::class)->engine)->toBeAnInstanceOf(W16::class);
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
            $di->offsetSet(Engine::class, V8::class);
            $di->offsetSet(Car::class, ['color' => 'blue']);

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
            $di->offsetSet(Engine::class, function($c, $make) use(&$v8, &$count) {
                $v8 = new V8;
                $count++;

                return $v8;
            });
            $di->offsetSet(Engine::class, function($c, $make) use(&$v8, &$count) {
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
            $di->offsetSet(Engine::class, V8::class);
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

        it('throw TypeError when key is not string', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetSet(new stdClass, 'value');
            })
            ->toThrow(new TypeError('Entry must be string'));
        });
    });

    describe('offsetGet', function() {
        it('calls param callable and return it\'t value', function() {
            $di = new Registry;
            $di->offsetSet('rand', function() {
                return 14;
            });

            expect($di->offsetGet('rand'))->toBe(14);
        });

        it('calls param callable with it\'s own instance as first argument', function() {
            $di = new Registry;
            $di->offsetSet('test', function($c) use($di) {
                expect($c)->toBe($di);
            });
            $di->offsetGet('test');
        });

        it('calls param callable everytime the parameter is requested', function() {
            $count = 0;

            $di = new Registry;
            $di->offsetSet('test', function() use(&$count) {
                $count++;
            });
            $di->offsetGet('test');
            $di->offsetGet('test');

            expect($count)->toBe(2);
        });

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

        it('calls factory with it\'s own instance and a make function', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, function($c, $make) use($di) {
                expect($c)->toBe($di);
                expect($make)->toBeAnInstanceOf(Closure::class);

                return new V8;
            });
            $di->offsetGet(Engine::class);
        });

        it('calls factory with make function that creates an instance from deleagte', function() {
            $delegate = new Registry;
            $delegate->offsetSet(V8::class, V8::class);

            $di = new Registry([], $delegate);
            $di->offsetSet(Engine::class, function($c, $make) {
                return $make(V8::class);
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(V8::class);
        });

        it('calls factory with make function that when called without parameters it creates an instance of the abstract from delegate', function() {
            $delegate = new Registry;
            $delegate->offsetSet(Engine::class, V8::class);

            $di = new Registry([], $delegate);
            $di->offsetSet(Engine::class, function($c, $make) {
                return $make();
            });

            expect($di->offsetGet(Engine::class))->toBeAnInstanceOf(V8::class);
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

        it('throws RuntimeException when entry not found', function() {
            $di = new Registry;

            expect(function() use($di) {
                $di->offsetGet('not_found');
            })
            ->toThrow(new RuntimeException('Entry [not_found] not found'));
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
            $di->offsetSet(Engine::class, V8::class);

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
        it('removes a parameter/factory', function() {
            $di = new Registry;
            $di->offsetSet('param', 'value');
            $di->offsetSet(Engine::class, V8::class);

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

            unset($di[$key]);

            expect(function() use($di, $key) {
                $di->offsetGet($key);
            })
            ->toThrow(new TypeError('Entry must be string'));
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
            $di->offsetSet(V8::class, V8::class);

            expect($di->make(W16::class, [V8::class, V8::class]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class with given arguments from name', function() {
            $di = new Registry;
            $v8 = new V8;

            expect($di->make(W16::class, ['left' => $v8, 'right' => $v8]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class resolving dependency by class in the argument', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, V8::class);

            expect($di->make(W16::class, ['left' => V8::class, 'right' => V8::class]))->toBeAnInstanceOf(W16::class);
        });

        it('creates a concrete class resolving dependency by param in the argument prefixed with dollar sign', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, V8::class);
            $di->offsetSet('color', 'blue');

            expect($di->make(Car::class, ['color' => '$color'])->color)->toBe('blue');
        });

        it('creates a concrete class with delegate lookup the parameter type hint', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, V8::class);

            expect($di->make(W16::class))->toBeAnInstanceOf(W16::class);
        });

        // require pdo-sqlite
        xit('creates a concrete class with no default values but optional', function() {
            $di = new Registry;

            allow(PDO::class)->toBeOk();
            expect($di->make(PDO::class, ['sqlite://:memory:']))->toBeAnInstanceOf(PDO::class);
        });

        it('creates a concrete class with the parameter default value', function() {
            $di = new Registry;
            $di->offsetSet(Engine::class, V8::class);

            $car = $di->make(Car::class);

            expect($car)->toBeAnInstanceOf(Car::class);
            expect($car->color)->toBe('red');
        });

        it('throws InvalidArgumentException when given class is abstract or interface', function() {
            expect(function() {
                $di = new Registry;
                $di->make(Engine::class);
            })
            ->toThrow(new InvalidArgumentException('Target [Engine] cannot be construct'));
        });

        it('throws InvalidArgumentException when resolved class is abstract or interface', function() {
            expect(function() {
                $di = new Registry;
                $di->offsetSet(Engine::class, function($c, $make) { return $make(Engine::class); });
                $di->make(Car::class);
            })
            ->toThrow(new InvalidArgumentException('Target [Engine] cannot be construct while [Car]'));
        });

        it('throws RuntimeException when a cyclic dependency is detected', function() {
            expect(function() {
                $di = new Registry;
                $di->offsetSet(Engine::class, W16::class);
                $di->make(W16::class);
            })
            ->toThrow(new RuntimeException('Cyclic dependency detected for [W16]'));
        });

        it('throws LogicException when a parameter is not found', function() {
            expect(function() {
                $di = new Registry;
                $di->make(Car::class);
            })
            ->toThrow(new LogicException('Parameter [engine] not found for [Car]'));
        });
    });
});