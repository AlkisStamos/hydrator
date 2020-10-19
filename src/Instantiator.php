<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Hydrator;

use ReflectionClass;
use ReflectionException;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Instantiates and keeps local copies of reflections and class instances
 */
class Instantiator implements InstantiatorInterface
{
    /**
     * In memory cache of handled instances
     *
     * @var mixed[]
     */
    protected $instances = [];
    /**
     * In memory cache of handled ReflectionClasses
     *
     * @var ReflectionClass[]
     */
    protected $reflections = [];

    /**
     * Instantiates a class by its name. By default the method will instantiate without constructor usage. If the
     * constructorArgs parameter is set the method will force a new instance by passing the arguments in the constructor
     *
     * @param string $class
     * @param array|null $constructorArgs
     * @return mixed
     * @throws ReflectionException
     */
    public function instantiate(string $class, ?array $constructorArgs = null)
    {
        if (!isset($this->instances[$class])) {
            $reflection = $this->getReflectionClass($class);
            $this->instances[$class] = $constructorArgs === null ?
                $reflection->newInstanceWithoutConstructor() :
                $reflection->newInstanceArgs($constructorArgs);
        }
        return clone $this->instances[$class];
    }

    /**
     * Generates and caches a new reflection class from the class name.
     *
     * @param string $class
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public function getReflectionClass(string $class): ReflectionClass
    {
        if (!isset($this->reflections[$class])) {
            $this->reflections[$class] = new ReflectionClass($class);
        }
        return $this->reflections[$class];
    }
}