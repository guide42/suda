<?php

namespace Guide42\Suda\Tests\Fixtures;

abstract class GreeterPerson implements GreeterPersonInterface
{
    public $greeter;

    public function __construct(GreeterInterface $greeter)
    {
        $this->greeter = $greeter;
    }

    abstract public function getName();

    public function greet()
    {
        return $this->getName() . ' says ' . $this->greeter->greet();
    }
}