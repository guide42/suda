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

    public function testGetAll()
    {
        $registry = new Registry();
        $registry->register(new GreeterService('Bob'), 'bob');
        $registry->register(new GreeterService('Ted'), 'ted');

        $services = $registry->getAll('GreeterInterface');

        $this->assertInternalType('array', $services);
        $this->assertEquals(array('bob', 'ted'), array_keys($services));
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Services for ArrayAccess not found
     */
    public function testGetAllWithNonexistentThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->getAll('ArrayAccess');
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Factory must implement at least one interface
     */
    public function testRegisterFactoryWithInvalid()
    {
        $registry = new Registry();
        $registry->registerFactory('InvalidService');
    }

    public function testRegisterFactory()
    {
        $registry = new Registry();
        $registry->registerFactory('EvaPersonGreeter', 'eva', array('Person'));

        $context = array(new Bob());
        $object = $registry->get('PersonGreeterInterface', 'eva', $context);

        $this->assertInstanceOf('PersonGreeter', $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testRegisterFactoryWithoutContext()
    {
        $registry = new Registry();
        $registry->register(new Bob());
        $registry->registerFactory('EvaPersonGreeter', 'eva', array('Person'));

        $object = $registry->get('PersonGreeterInterface', 'eva');

        $this->assertInstanceOf('PersonGreeter', $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testRegisterFactoryWithArgumentName()
    {
        $registry = new Registry();
        $registry->register(new Bob(), 'bob');
        $registry->registerFactory('EvaPersonGreeter', 'eva',
            array(array('Person', 'bob')));

        $object = $registry->get('PersonGreeterInterface', 'eva');

        $this->assertInstanceOf('PersonGreeter', $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testGetAllDoesntIncludeFactories()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->registerFactory('EvaPersonGreeter', 'eva', array('Person'));

        $services = $registry->getAll('GreeterInterface');

        $this->assertInternalType('array', $services);
        $this->assertEquals(array(''), array_keys($services));
    }

    public function testHas()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());

        $this->assertTrue($registry->has('GreeterInterface'));
        $this->assertFalse($registry->has('GreeterInterface', 'world'));
        $this->assertFalse($registry->has('PersonGreeterInterface'));
    }

    public function testGetWillCacheReflexionWhenUsedTwice()
    {
        $registry = new Registry();
        $registry->registerFactory('EvaPersonGreeter', 'e1', array('Person'));
        $registry->registerFactory('EvaPersonGreeter', 'e2', array('Person'));

        $context = array(new Bob());

        $obj1 = $registry->get('PersonGreeterInterface', 'e1', $context);
        $obj2 = $registry->get('PersonGreeterInterface', 'e2', $context);

        $this->assertNotSame($obj1, $obj2);
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
interface PersonGreeterInterface {}

class Bob implements Person { public function getName() { return 'Bob'; } }
class Ted implements Person { public function getName() { return 'Ted'; } }

class PersonGreeter extends GreeterService implements PersonGreeterInterface {
    public function __construct(Person $person) {
        parent::__construct($person->getName());
    }
}

abstract class PersonPersonGreeter extends PersonGreeter implements Person {
    public function greet() {
        return parent::greet() . ', my name is ' . $this->getName();
    }
}

class EvaPersonGreeter extends PersonPersonGreeter {
    public function getName() { return 'Eva'; }
}