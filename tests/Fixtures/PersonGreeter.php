<?php

namespace Guide42\Suda\Tests\Fixtures;

class PersonGreeter extends GreeterService implements PersonGreeterInterface
{
    public function __construct(Person $person)
    {
        parent::__construct($person->getName());
    }
}