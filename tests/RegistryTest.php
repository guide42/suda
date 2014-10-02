<?php

use Guide42\Suda\Registry;

class RegistryTest extends \PHPUnit_Framework_TestCase
{
    // Step 1
    // Implement a simple array that will contain configuration parameters
    // that could be whatever.

    public function testSettings()
    {
        $registry = new Registry();

        $this->assertInternalType('array', $registry->settings);
        $this->assertEmpty($registry->settings);
    }
}