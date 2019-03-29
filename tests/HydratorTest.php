<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Tests;
use AlkisStamos\Metadata\Hydrator;
use AlkisStamos\Metadata\InstantiatorInterface;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    public function testHydrateWithDefaultDriverEmptyData()
    {
        $hydrator = new Hydrator();
        $className = MockEmptyClass::class;
        $instance = new MockEmptyClass();
        $instantiator = $this->createMock(InstantiatorInterface::class);
        $instantiator->expects($this->once())
            ->method('instantiate')
            ->with($className)
            ->willReturn($instance);
        $instantiator->expects($this->once())
            ->method('getReflectionClass')
            ->with($className)
            ->willReturn(new \ReflectionClass($className));
        $hydrator->setInstantiator($instantiator);
        $hydratedInstance = $hydrator->hydrate([],$className);
        $this->assertInstanceOf($className,$hydratedInstance);
        $this->assertSame($instance, $hydratedInstance);
    }
}
class MockEmptyClass {}