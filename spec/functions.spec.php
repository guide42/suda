<?php declare(strict_types=1);

use suda\Registry;
use function suda\{ref, build, alias, automake};

require_once 'engine.fixture.php';

describe('functions', function() {
    describe('ref', function() {
        it('is a factory that returns the given object', function() {
            $v8 = new V8;
            $di = new Registry;
            $di->offsetSet(V8::class, ref($v8));

            expect($di->offsetGet(V8::class))->toBe($v8);
        });
    });

    describe('build', function() {
        it('is a factory that creates the given class', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, build(V8::class));

            expect($di->offsetGet(V8::class))->toBeAnInstanceOf(V8::class);
        });

        it('is a factory that creates the given class with arguments', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, build(V8::class));
            $di->offsetSet(W16::class, build(W16::class, [
                V8::class,
                V8::class,
            ]));

            $w16 = $di->offsetGet(W16::class);

            expect($w16->left)->toBeAnInstanceOf(V8::class);
            expect($w16->right)->toBeAnInstanceOf(V8::class);
        });
    });

    describe('alias', function() {
        it('is a factory that retrieve key from given registry', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, build(V8::class));
            $di->offsetSet('v8', alias($di, V8::class));

            expect($di->offsetGet('v8'))->toBeAnInstanceOf(V8::class);
        });
    });

    describe('automake', function() {
        it('is a factory that creates the service automatically', function() {
            $di = new Registry;
            $di->offsetSet(V8::class, automake());

            expect($di->offsetGet(V8::class))->toBeAnInstanceOf(V8::class);
        });
    });
});
