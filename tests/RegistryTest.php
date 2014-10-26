<?php

namespace Guide42\SudaTest;

use Guide42\Suda\Registry;

use Guide42\SudaTest\Fixtures\InvalidService;
use Guide42\SudaTest\Fixtures\GreeterService;
use Guide42\SudaTest\Fixtures\PersonGreeter;
use Guide42\SudaTest\Fixtures\Person;
use Guide42\SudaTest\Fixtures\Bob;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    private $ns = 'Guide42\\SudaTest\\Fixtures';

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
        $registry->register(new InvalidService());
    }

    public function testRegister()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());

        $object = $registry->get("$this->ns\\GreeterInterface");

        $this->assertInstanceOf("$this->ns\\GreeterInterface", $object);
        $this->assertEquals('Hello World', $object->greet());
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Service "" for ArrayAccess not found
     */
    public function testGetWithNonexistentThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->get('ArrayAccess');
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Service "" for Guide42\SudaTest\Fixtures\GreeterService not found
     */
    public function testGetWithClassThrowsLookupException()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->get("$this->ns\\GreeterService");
    }

    public function testRegisterReplace()
    {
        $registry = new Registry();
        $registry->register(new GreeterService('Bob'));
        $registry->register(new GreeterService('Ted'));

        $object = $registry->get("$this->ns\\GreeterInterface");

        $this->assertInstanceOf("$this->ns\\GreeterInterface", $object);
        $this->assertEquals('Hello Ted', $object->greet());
    }

    public function testGetSame()
    {
        $registry = new Registry();
        $registry->register($object = new GreeterService());

        $objectOne = $registry->get("$this->ns\\GreeterInterface");
        $objectTwo = $registry->get("$this->ns\\GreeterInterface");

        $this->assertInstanceOf("$this->ns\\GreeterInterface", $objectOne);
        $this->assertInstanceOf("$this->ns\\GreeterInterface", $objectTwo);
        $this->assertSame($objectOne, $objectTwo);
        $this->assertSame($object, $objectOne);
        $this->assertSame($object, $objectTwo);
    }

    public function testGetSameWithDifferentInterfaces()
    {
        $registry = new Registry();
        $registry->register($object = new GreeterService());

        $objectOne = $registry->get("$this->ns\\GreeterInterface");
        $objectTwo = $registry->get("$this->ns\\GoodbyeInterface");

        $this->assertInstanceOf("$this->ns\\GreeterInterface", $objectOne);
        $this->assertInstanceOf("$this->ns\\GreeterInterface", $objectTwo);
        $this->assertSame($objectOne, $objectTwo);
        $this->assertSame($object, $objectOne);
        $this->assertSame($object, $objectTwo);
    }

    public function testRegisterInheritInterface()
    {
        $registry = new Registry();
        $registry->register(new PersonGreeter(new Bob()));

        $bob = $registry->get("$this->ns\\GreeterInterface");

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $bob);
        $this->assertInstanceOf("$this->ns\\GreeterInterface", $bob);
        $this->assertEquals('Hello Bob', $bob->greet());
    }

    public function testWithName()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->register($greetBob = new GreeterService('Bob'), 'bob');
        $registry->register($greetTed = new GreeterService('Ted'), 'ted');

        $retBob = $registry->get("$this->ns\\GreeterInterface", 'bob');
        $retTed = $registry->get("$this->ns\\GreeterInterface", 'ted');

        $this->assertInstanceOf("$this->ns\\GreeterInterface", $retBob);
        $this->assertInstanceOf("$this->ns\\GreeterInterface", $retTed);

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

        $services = $registry->getAll("$this->ns\\GreeterInterface");

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
    public function testRegisterDefinitionWithInvalid()
    {
        $registry = new Registry();
        $registry->registerDefinition("$this->ns\\InvalidService");
    }

    public function testRegisterDefinition()
    {
        $registry = new Registry();
        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'eva',
            array("$this->ns\\Person")
        );

        $object = $registry->get("$this->ns\\PersonGreeterInterface", 'eva',
            array(new Bob())
        );

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testRegisterDefinitionWithoutContext()
    {
        $registry = new Registry();
        $registry->register(new Bob());
        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'eva',
            array("$this->ns\\Person")
        );

        $object = $registry->get("$this->ns\\PersonGreeterInterface", 'eva');

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testRegisterDefinitionWithArgumentName()
    {
        $registry = new Registry();
        $registry->register(new Bob(), 'bob');
        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'eva',
            array( // arguments
                array("$this->ns\\Person", 'bob'),
            )
        );

        $object = $registry->get("$this->ns\\PersonGreeterInterface", 'eva');

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $object);
        $this->assertEquals('Hello Bob, my name is Eva', $object->greet());
    }

    public function testRegisterDefinitionWithArgumentLiteral()
    {
        $registry = new Registry();
        $registry->register(new Bob(), 'bob');
        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'eva',
            array( // arguments
                array("$this->ns\\Person", 'bob'),
                42
            )
        );

        $object = $registry->get("$this->ns\\PersonGreeterInterface", 'eva');

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $object);
        $this->assertEquals('Hello Bob, my name is Eva (42 years old)',
            $object->greet());
    }

    public function testGetAllDoesntIncludeFactories()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());
        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'eva',
            array("$this->ns\\Person")
        );

        $services = $registry->getAll("$this->ns\\GreeterInterface");

        $this->assertInternalType('array', $services);
        $this->assertEquals(array(''), array_keys($services));
    }

    public function testHas()
    {
        $registry = new Registry();
        $registry->register(new GreeterService());

        $this->assertTrue($registry->has("$this->ns\\GreeterInterface"));
        $this->assertFalse($registry->has("$this->ns\\GreeterInterface", 'w'));
    }

    public function testGetWillCacheReflexionWhenUsedTwice()
    {
        $registry = new Registry();

        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'e1',
            array("$this->ns\\Person")
        );

        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", 'e2',
            array("$this->ns\\Person")
        );

        $obj1 = $registry->get("$this->ns\\PersonGreeterInterface", 'e1',
            array(new Bob()));

        $obj2 = $registry->get("$this->ns\\PersonGreeterInterface", 'e2',
            array(new Bob()));

        $this->assertNotSame($obj1, $obj2);
    }

    /**
     * @expectedException        \LogicException
     * @expectedExceptionMessage Cyclic dependency detected for Guide42\SudaTest\Fixtures\EvaPersonGreeter
     */
    public function testRecursivity()
    {
        $registry = new Registry();

        $registry->registerDefinition("$this->ns\\Bob");

        $registry->registerDefinition("$this->ns\\BobGreeter", '',
            array("$this->ns\\PersonGreeterInterface")
        );

        $registry->registerDefinition("$this->ns\\EvaPersonGreeter", '',
            array("$this->ns\\Person")
        );

        $registry->get("$this->ns\\GreeterInterface");
    }

    public function testRegisterFactory()
    {
        $registry = new Registry();
        $registry->register(new Bob());
        $registry->registerFactory("$this->ns\\PersonGreeterInterface",
        function(Person $person, $debug=false, $swag='optional') {
            if ($debug) {
                // Do something
            }
            return new PersonGreeter($person);
        });

        $object = $registry->get("$this->ns\\PersonGreeterInterface", '',
            array(1 => true)
        );

        $this->assertInstanceOf("$this->ns\\PersonGreeter", $object);
        $this->assertEquals('Hello Bob', $object->greet());
    }

    public function testDelegateLookup()
    {
        $registry0 = new Registry();
        $registry0->register(new GreeterService('Mundo'));

        $registry1 = new Registry($registry0);
        $registry1->registerDefinition("$this->ns\\BobGreeter", '',
            array("$this->ns\\GreeterInterface")
        );

        $greeter = $registry1->get("$this->ns\\GreeterPersonInterface");

        $this->assertInstanceOf("$this->ns\\BobGreeter", $greeter);
        $this->assertEquals('Bob says Hello Mundo', $greeter->greet());
    }

    public function testSetDelegateLookupContainer()
    {
        $registry0 = new Registry();
        $registry0->register(new GreeterService('Mundo'));

        $registry1 = new Registry();
        $registry1->setDelegateLookupContainer($registry0);
        $registry1->registerDefinition("$this->ns\\BobGreeter", '',
            array("$this->ns\\GreeterInterface")
        );

        $greeter = $registry1->get("$this->ns\\GreeterPersonInterface");

        $this->assertInstanceOf("$this->ns\\BobGreeter", $greeter);
    }

    public function testIsInteropContainerInterface()
    {
        $registry = new Registry();
        $interface = 'Interop\Container\ContainerInterface';

        $this->assertInstanceOf($interface, $registry);
    }
}