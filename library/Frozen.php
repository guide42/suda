<?php declare(strict_types=1);

namespace suda;

class Frozen extends \RuntimeException
{
    protected /*?string */$entry;

    function __construct(string $entry=null, string $message=null, long $code=null, \Throwable $previous=null) {
        $this->entry = $entry;

        parent::__construct(
            $message ?? ($entry ? "Entry [$entry] is frozen" : 'Registry is frozen'),
            $code ?? 0,
            $previous
        );
    }

    function getEntry(): ?string {
        return $this->entry;
    }
}