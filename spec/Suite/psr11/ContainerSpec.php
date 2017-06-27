<?php declare(strict_types=1);

use suda\Registry;
use suda\psr11\Container;
use suda\psr11\ContainerException;
use suda\psr11\NotFoundException;

class FakeContainer implements \Psr\Container\ContainerInterface {
    function get($id) {
        if ($id === 'message') {
            return 'All your base are belong to us';
        }
        return null;
    }

    function has($id) {
        return $id === 'message';
    }
}

describe('Container', function() {
    describe('__construct', function() {
        it('accepts a Registry', function() {
            expect(new Container(new Registry))->toBeAnInstanceOf(Container::class);
        });
    });

    describe('withDelegate', function() {
        it('accepts a container as delegate', function() {
            $container = new Container(new Registry([Exception::class => ['$message']]));
            $container = $container->withDelegate(new FakeContainer);

            $service = $container->get(Exception::class);

            expect($service)->toBeAnInstanceOf(Exception::class);
            expect($service->getMessage())->toBe('All your base are belong to us');
        });
    });

    describe('get', function() {
        it('returns param', function() {
            $di = new Registry;
            $di['param'] = 'value';

            expect((new Container($di))->get('param'))->toBe('value');
        });
        it('returns service', function() {
            $di = new Registry;
            $di[stdClass::class] = function() {
                return new stdClass;
            };

            expect((new Container($di))->get(stdClass::class))->toBeAnInstanceOf(stdClass::class);
        });
        it('throws NotFoundException when entry is not found', function() {
            expect(function() {
                $container = new Container(new Registry);
                $container->get('not_found');
            })
            ->toThrow(new NotFoundException);
        });
        it('throws ContainerException when factory returns invalid instance', function() {
            $di = new Registry;
            $di[stdClass::class] = function() {
                return new SplFixedArray;
            };

            expect(function() use($di) {
                (new Container($di))->get(stdClass::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when abstract class being make in factory', function() {
            $di = new Registry;
            $di[stdClass::class] = function($c, $make) {
                $make(Countable::class);
            };

            expect(function() use($di) {
                (new Container($di))->get(stdClass::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when parameter is not found', function() {
            $di = new Registry;
            $di[ParentIterator::class] = ParentIterator::class;

            expect(function() use($di) {
                (new Container($di))->get(ParentIterator::class);
            })
            ->toThrow(new ContainerException);
        });
        it('throws ContainerException when cyclic dependency happen', function() {
            $di = new Registry;
            $di[ParentIterator::class] = [ParentIterator::class];

            expect(function() use($di) {
                (new Container($di))->get(ParentIterator::class);
            })
            ->toThrow(new ContainerException);
        });
    });

    describe('has', function() {
        it('returns true when param exists', function() {
            $di = new Registry;
            $di['param'] = 'value';

            expect((new Container($di))->has('param'))->toBe(true);
        });
    });
});