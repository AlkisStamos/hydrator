<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Generates object and reflection instances for classes
 */
interface InstantiatorInterface
{
    /**
     * Instantiates a class by its name.
     *
     * @param string $class
     * @param array|null $constructorArgs
     * @return mixed
     */
    public function instantiate(string $class, ?array $constructorArgs=null);

    /**
     * Returns the reflection class of the class parameter
     *
     * @param string $class
     * @return \ReflectionClass
     */
    public function getReflectionClass(string $class);
}