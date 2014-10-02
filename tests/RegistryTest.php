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
     * @expectedExceptionMessage Service "" for ArrayAccess not found
     */
    public function testGetWithNonexistentThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new \GreeterService());
        $registry->get('ArrayAccess');
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Service "" for GreeterService not found
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

    public function testRegisterInheritInterface()
    {
        $registry = new Registry();
        $registry->register(new PersonGreeter(new Bob()));

        $bob = $registry->get('GreeterInterface');

        $this->assertInstanceOf('PersonGreeter', $bob);
        $this->assertInstanceOf('GreeterInterface', $bob);
        $this->assertEquals('Hello Bob', $bob->greet());
    }

    public function testWithName()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->register($greetBob = new GreeterService('Bob'), 'bob');
        $registry->register($greetTed = new GreeterService('Ted'), 'ted');

        $retBob = $registry->get('GreeterInterface', 'bob');
        $retTed = $registry->get('GreeterInterface', 'ted');

        $this->assertInstanceOf('GreeterInterface', $retBob);
        $this->assertInstanceOf('GreeterInterface', $retTed);

        $this->assertSame($greetBob, $retBob);
        $this->assertSame($greetTed, $retTed);

        $this->assertEquals('Hello Bob', $retBob->greet());
        $this->assertEquals('Hello Ted', $retTed->greet());
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

interface Person { function getName(); }

class Bob implements Person { public function getName() { return 'Bob'; } }
class Ted implements Person { public function getName() { return 'Ted'; } }

class PersonGreeter extends GreeterService {
    public function __construct(Person $person) {
        parent::__construct($person->getName());
    }
}