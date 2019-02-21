<?php declare(strict_types=1);

use suda\Frozen;

describe('Frozen', function() {
    describe('__construct', function() {
        it('accepts no arguments', function() {
            expect((new Frozen)->getEntry())->toBeNull();
        });
        it('accepts a string entry', function() {
            $entry = 'foo';
            $error = new Frozen($entry);

            expect($error->getEntry())->toBe($entry);
        });
    });
});