<?php

use Guide42\Suda\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testSettings()
    {
        $registry = new Registry();

        $this->assertInternalType('array', $registry->settings);
        $this->assertEmpty($registry->settings);
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Service must implement at least one interface
     */
    public function testRegisterWithInvalid()
    {
        $registry = new Registry();
        $registry->register(new \InvalidService());
    }

    public function testRegister()
    {
        $registry = new Registry();
        $registry->register(new \GreeterService());

        $object = $registry->get('GreeterInterface');

        $this->assertInstanceOf('GreeterInterface', $object);
        $this->assertEquals('Hello World', $object->greet());
    }
}

### FIXTURES ##################################################################

interface GreeterInterface { function greet(); }

class InvalidService {}
class GreeterService implements GreeterInterface {
    public function greet() {
        return 'Hello World';
    }
}