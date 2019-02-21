<?php declare(strict_types=1);

use suda\NotFound;

describe('NotFound', function() {
    describe('__construct', function() {
        it('accepts a string entry', function() {
            $entry = 'foo';
            $error = new NotFound($entry);

            expect($error->getEntry())->toBe($entry);
        });
    });
});