<?php declare(strict_types=1);

use suda\CyclicDependency;

describe('CyclicDependency', function() {
    describe('__construct', function() {
        it('accepts a string class name', function() {
            $class = 'suda\\Registry';
            $error = new CyclicDependency($class);

            expect($error->getClassName())->toBe($class);
        });
    });
});