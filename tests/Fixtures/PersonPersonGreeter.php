<?php

namespace Guide42\Suda\Tests\Fixtures;

abstract class PersonPersonGreeter extends PersonGreeter implements Person
{
    private $age;

    public function __construct(Person $person, $age=null)
    {
        $this->age = $age;

        parent::__construct($person);
    }

    public function greet()
    {
        return parent::greet() . ', my name is ' . $this->getName() .
            ($this->age ? ' (' . $this->age . ' years old)' : '');
    }
}