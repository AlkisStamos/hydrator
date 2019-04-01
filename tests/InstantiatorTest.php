<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Hydrator\Tests;
use AlkisStamos\Hydrator\Instantiator;
use PHPUnit\Framework\TestCase;

class InstantiatorTest extends TestCase
{
    public function testInstantiate()
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate(MockInstance::class);
        $this->assertInstanceOf(MockInstance::class, $instance);
    }

    public function testInstantiatorWithCache()
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate(MockInstance::class);
        $this->assertInstanceOf(MockInstance::class, $instance);
        $this->assertEquals($instantiator->instantiate(MockInstance::class), $instance);
    }

    public function testInstantiatorWithoutConstructor()
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate(MockInstance::class);
        $this->assertInstanceOf(MockInstance::class, $instance);
        $this->assertEquals(1, $instance->a);
        $this->assertEquals(2, $instance->b);
    }

    public function testInstantiatorWithContructorDefaultValues()
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate(MockInstance::class, []);
        $this->assertInstanceOf(MockInstance::class, $instance);
        $this->assertEquals(3, $instance->a);
        $this->assertEquals(4, $instance->b);
    }

    public function testInstantiatorWithContructorArguments()
    {
        $instantiator = new Instantiator();
        $instance = $instantiator->instantiate(MockInstance::class, ['a', 'b']);
        $this->assertInstanceOf(MockInstance::class, $instance);
        $this->assertEquals('a', $instance->a);
        $this->assertEquals('b', $instance->b);
    }

    public function testGetReflectionClass()
    {
        $instantiator = new Instantiator();
        $reflection = $instantiator->getReflectionClass(MockInstance::class);
        $this->assertInstanceOf(\ReflectionClass::class, $reflection);
        $this->assertEquals($reflection->getName(), MockInstance::class);
    }

    public function testGetReflectionWithCache()
    {
        $instantiator = new Instantiator();
        $reflection = $instantiator->getReflectionClass(MockInstance::class);
        $this->assertInstanceOf(\ReflectionClass::class, $reflection);
        $this->assertEquals($reflection->getName(), MockInstance::class);
        $this->assertEquals($reflection, $instantiator->getReflectionClass(MockInstance::class));
    }
}

class MockInstance
{
    public $a = 1;
    public $b = 2;

    public function __construct($a=3, $b=4)
    {
        $this->a = $a;
        $this->b = $b;
    }
}