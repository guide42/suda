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

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Service for ArrayAccess not found
     */
    public function testGetWithNonexistentThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new \GreeterService());
        $registry->get('ArrayAccess');
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Service for GreeterService not found
     */
    public function testGetWithClassThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new \GreeterService());
        $registry->get('GreeterService');
    }

    public function testRegisterReplace()
    {
        $registry = new Registry();
        $registry->register(new \GreeterService('Bob'));
        $registry->register(new \GreeterService('Ted'));

        $object = $registry->get('GreeterInterface');

        $this->assertInstanceOf('GreeterInterface', $object);
        $this->assertEquals('Hello Ted', $object->greet());
    }

    public function testGetSame()
    {
        $registry = new Registry();
        $registry->register($object = new \GreeterService());

        $objectOne = $registry->get('GreeterInterface');
        $objectTwo = $registry->get('GreeterInterface');

        $this->assertInstanceOf('GreeterInterface', $objectOne);
        $this->assertInstanceOf('GreeterInterface', $objectTwo);
        $this->assertSame($objectOne, $objectTwo);
        $this->assertSame($object, $objectOne);
        $this->assertSame($object, $objectTwo);
    }

    public function testGetSameWithDifferentInterfaces()
    {
        $registry = new Registry();
        $registry->register($object = new \GreeterService());

        $objectOne = $registry->get('GreeterInterface');
        $objectTwo = $registry->get('GoodbyeInterface');

        $this->assertInstanceOf('GreeterInterface', $objectOne);
        $this->assertInstanceOf('GreeterInterface', $objectTwo);
        $this->assertSame($objectOne, $objectTwo);
        $this->assertSame($object, $objectOne);
        $this->assertSame($object, $objectTwo);
    }
}

### FIXTURES ##################################################################

interface GreeterInterface { function greet(); }
interface GoodbyeInterface {}

class InvalidService {}
class GreeterService implements GreeterInterface, GoodbyeInterface {
    public $other;
    public function __construct($other='World') {
        $this->other = $other;
    }
    public function greet() {
        return 'Hello ' . $this->other;
    }
}