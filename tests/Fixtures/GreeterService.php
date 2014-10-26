<?php

namespace Guide42\SudaTest\Fixtures;

class GreeterService implements GreeterInterface, GoodbyeInterface
{
    public $other;

    public function __construct($other='World')
    {
        $this->other = $other;
    }

    public function greet()
    {
        return 'Hello ' . $this->other;
    }
}