<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Hydrator\Tests;

use Alks\Hydrator\Hydrator;
use Alks\Hydrator\HydratorHookInterface;
use Alks\Hydrator\InstantiatorInterface;
use Alks\Hydrator\Resolver\PropertyValueResolverInterface;
use Alks\Metadata\Driver\MetadataDriverInterface;
use Alks\Metadata\Metadata\ClassMetadata;
use Alks\Metadata\Metadata\PropertyMetadata;
use Alks\Metadata\MetadataDriver;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    public function testHydrateWithDefaultDriverEmptyData()
    {
        $hydrator = new Hydrator();
        $className = MockEmptyClass::class;
        $instance = new MockEmptyClass();
        $instantiator = $this->createMock(InstantiatorInterface::class);
        $instantiator->expects($this->once())->method('instantiate')->with($className)->willReturn($instance);
        $instantiator->expects($this->once())->method('getReflectionClass')->with($className)->willReturn(new \ReflectionClass($className));
        $hydrator->setInstantiator($instantiator);
        $hydratedInstance = $hydrator->hydrate([], $className);
        $this->assertInstanceOf($className, $hydratedInstance);
        $this->assertSame($instance, $hydratedInstance);
    }

    public function testHydrateEmptyData()
    {
        $hydrator = new Hydrator();
        $instance = $hydrator->hydrate([], MockHydrateClass1::class);
        $this->assertInstanceOf(MockHydrateClass1::class, $instance);
        $this->assertNull($instance->prop1);
        $this->assertNull($instance->prop2);
    }

    public function testHydrate()
    {
        $hydrator = new Hydrator();
        $data = ['prop1' => 'prop1', 'prop2' => 'prop2'];
        $instance = $hydrator->hydrate($data, MockHydrateClass1::class);
        $this->assertInstanceOf(MockHydrateClass1::class, $instance);
        $this->assertSame($data['prop1'], $instance->prop1);
        $this->assertSame($data['prop2'], $instance->prop2);
    }

    public function testHydrateWithCustomHook()
    {
        $data = ['prop1' => 'prop1', 'prop2' => 'prop2'];
        $instance = new MockHydrateClass1();
        $reflection = new \ReflectionClass(MockHydrateClass1::class);
        $propertyMetadata = $this->createMock(PropertyMetadata::class);
        $propertyMetadata->type = new \stdClass();
        $propertyMetadata->type->isNullable = false;
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->properties = [$propertyMetadata];
        $instantiator = $this->createMock(InstantiatorInterface::class);
        $instantiator->expects($this->once())->method('getReflectionClass')->with(MockHydrateClass1::class)->willReturn($reflection);
        $instantiator->expects($this->once())->method('instantiate')->with(MockHydrateClass1::class)->willReturn($instance);
        $driver = $this->createMock(MetadataDriverInterface::class);
        $driver->expects($this->once())->method('getClassMetadata')->willReturn($classMetadata);
        $hook = $this->createMock(HydratorHookInterface::class);
        $hook->expects($this->once())->method('onBeforeHydrate')->with($classMetadata, $data);
        $hook->expects($this->once())->method('onPropertyHydrate')->with($classMetadata, $propertyMetadata, $data, false);
        $hook->expects($this->once())->method('onAfterHydrate')->with($classMetadata, $instance);
        $hydrator = new Hydrator($driver, $instantiator);
        $hydrator->attachHook($hook);
        $this->assertSame($instance, $hydrator->hydrate($data, MockHydrateClass1::class));
    }

    public function testInstantiateWithCustomMetadataDriver()
    {
        $driver1 = $this->createMock(MetadataDriverInterface::class);
        $hydrator1 = new Hydrator($driver1);
        $this->assertNotSame($hydrator1->getMetadataDriver(), $driver1);
        $driver2 = new MetadataDriver();
        $hydrator2 = new Hydrator($driver2);
        $this->assertSame($driver2, $hydrator2->getMetadataDriver());
    }

    public function testHydrateWithCustomNestedProfile()
    {
        $resolver = $this->createMock(PropertyValueResolverInterface::class);
        $resolver->expects($this->exactly(2))->method('supports')->willReturn(true);
        $resolver->expects($this->exactly(2))->method('resolveProperty')->will($this->onConsecutiveCalls('empty.value', 'parent.child.grandchild'));
        $data = ['parent' => ['child' => ['grandchild' => 'thisisthevalue']]];
        $hydrator = new Hydrator();
        $hydrator->addPropertyResolver($resolver);
        $instance = $hydrator->hydrate($data, MockHydrateClass1::class);
        $this->assertNull($instance->prop1);
        $this->assertEquals('thisisthevalue', $instance->prop2);
    }
}

class MockEmptyClass {}
class MockHydrateClass1
{
    public $prop1;
    public $prop2;
}