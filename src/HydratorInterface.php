<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Hydrator;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Hydrates and extracts data from PHP classes to arrays and back
 */
interface HydratorInterface
{
    /**
     * Hydrates the data into an object of type $class
     *
     * @param array $data The raw data to be hydrated
     * @param string|null $strategy The current hydration strategy
     * @param string $class Fully qualified class name
     * @return mixed An object of type $class
     */
    public function hydrate(array $data, string $class, ?string $strategy=null);

    /**
     * Extracts and object back to an array
     *
     * @param mixed $object
     * @param string|null $strategy The current hydration strategy
     * @return array
     */
    public function extract($object, ?string $strategy=null): array;

    /**
     * Attaches a hook listener to the hydrator
     *
     * @param HydratorHookInterface $hook
     * @return mixed
     */
    public function attachHook(HydratorHookInterface $hook);
}