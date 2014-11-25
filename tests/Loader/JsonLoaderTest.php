<?php

namespace Guide42\SudaTest\Loader;

use Guide42\Suda\Registry;
use Guide42\Suda\Loader\JsonLoader;

class JsonLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $loader;

    public function setUp()
    {
        $this->loader = new JsonLoader(new Registry);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('Guide42\Suda\Loader\JsonLoader', $this->loader);
    }

    /**
     * @dataProvider getResources
     */
    public function testSupports($supports, $resource)
    {
        $this->assertEquals(!!$supports, $this->loader->supports($resource));
    }

    public function getResources()
    {
        return array(
            array(false, null),
            array(false, false),
            array(false, 'foo'),
            array(false, 'bar.php'),
            array(false, 'baz.xml'),
            array(false, 'foobar.json'),

            array(true, __DIR__ . '/Fixtures/foobar.json'),
        );
    }
}