<?php declare(strict_types=1);

namespace suda;

class CyclicDependency extends \RuntimeException
{
    protected /*string */$class;

    function __construct(string $class, string $message=null, long $code=null, \Throwable $previous=null) {
        $this->class = $class;

        parent::__construct(
            $message ?? "Cyclic dependency detected for [$class]",
            $code ?? 0,
            $previous
        );
    }

    function getClassName(): string {
        return $this->class;
    }
}