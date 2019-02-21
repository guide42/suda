<?php declare(strict_types=1);

namespace suda;

class NotFound extends \RuntimeException
{
    protected /*string */$entry;

    function __construct(string $entry, string $message=null, long $code=null, \Throwable $previous=null) {
        $this->entry = $entry;

        parent::__construct(
            $message ?? "Entry [$entry] not found",
            $code ?? 0,
            $previous
        );
    }

    function getEntry(): string {
        return $this->entry;
    }
}