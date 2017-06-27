<?php declare(strict_types=1);

use suda\psr11\Container;
use suda\psr11\ContainerException;
use suda\psr11\NotFoundException;

describe('Container', function() {
    describe('__construct', function() {
        it('accepts values', function() {
            $di = new Container([
                'param' => 'value',
                stdClass::class => stdClass::class,
            ]);

            expect($di->get('param'))->toBe('value');
            expect($di->get(stdClass::class))->toBeAnInstanceOf(stdClass::class);
        });
        it('accepts container as delegate', function() {
            $delegate = new Container([RecursiveIterator::class => function() {
                return new RecursiveArrayIterator(['/' => ['plants', 'rocks']]);
            }]);

            expect((new Container([], $delegate))->make(ParentIterator::class))->toBeAnInstanceOf(ParentIterator::class);
        });
    });

    describe('get', function() {
        it('returns param', function() {
            $di = new Container;
            $di['param'] = 'value';

            expect($di->get('param'))->toBe('value');
        });
        it('returns service', function() {
            $di = new Container;
            $di[stdClass::class] = function() {
                return new stdClass;
            };

            expect($di->get(stdClass::class))->toBeAnInstanceOf(stdClass::class);
        });
        it('throws NotFoundException when entry is not found', function() {
            expect(function() {
                $di = new Container;
                $di->get('not_found');
            })
            ->toThrow(new NotFoundException);
        });
        it('throws ContainerException when factory returns invalid instance', function() {
            $di = new Container;
            $di[stdClass::class] = function() {
                return new SplFixedArray;
            };

            expect(function() use($di) {
                $di->get(stdClass::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when abstract class being make in factory', function() {
            $di = new Container;
            $di[stdClass::class] = function($c, $make) {
                $make(Countable::class);
            };

            expect(function() use($di) {
                $di->get(stdClass::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when parameter is not found', function() {
            $di = new Container;
            $di[ParentIterator::class] = ParentIterator::class;

            expect(function() use($di) {
                $di->get(ParentIterator::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when cyclic dependency happen', function() {
            $di = new Container;
            $di[ParentIterator::class] = [ParentIterator::class];

            expect(function() use($di) {
                $di->get(ParentIterator::class);
            })
            ->toThrow(new ContainerException);
        });
    });

    describe('has', function() {
        it('returns true when param exists', function() {
            $di = new Container;
            $di['param'] = 'value';

            expect($di->has('param'))->toBe(true);
        });
    });
});