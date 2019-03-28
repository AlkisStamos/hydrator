<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Cast;
use AlkisStamos\Metadata\Metadata\TypeMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Type casts flat properties
 */
class FlatTypeCastStrategy implements TypeCastStrategyInterface
{
    /**
     * Defines the name of the strategy the type casting should run. If the method returns null the type cast should
     * be used when no strategy is defined or as a fallback.
     *
     * @return null|string
     */
    public function strategy(): ?string
    {
        return null;
    }

    /**
     * Returns the list of custom/flat types the service supports
     *
     * @return array
     */
    public function supports(): array
    {
        return ['string', 'null', 'NULL', 'boolean', 'bool', 'integer', 'int', 'float', 'double'];
    }

    /**
     * Runs the type cast methods on extract direction (when an object is being converted to an array) for the allowed
     * strategy
     *
     * @param TypeMetadata $metadata
     * @param $value
     * @return mixed
     */
    public function extract(TypeMetadata $metadata, $value)
    {
        return $this->hydrate($metadata,$value);
    }

    /**
     * Runs the type cast methods on hydration direction (when an array is being converted to an object) for the allowed
     * strategy
     *
     * @param TypeMetadata $metadata
     * @param $value
     * @return mixed
     */
    public function hydrate(TypeMetadata $metadata, $value)
    {
        $type = $metadata->name;
        if($metadata->isArray && is_array($value))
        {
            if($type === 'object')
            {
                return $this->objectCast($value);
            }
            if($type === 'array' || $type === 'mixed')
            {
                return $value;
            }
            return $this->arrayCast($type,$value);
        }
        return $this->flatCast($type,$value);
    }

    /**
     * Casts the values of an array into the given type. Eg: string[]
     *
     * @param $type
     * @param array $values
     * @return array
     */
    protected function arrayCast($type, array $values)
    {
        $res = [];
        foreach($values as $key => $value)
        {
            if(is_array($value))
            {
                $res[$key] = $this->arrayCast($type, $value);
            }
            else
            {
                $res[$key] = $this->flatCast($type, $value);
            }
        }
        return $res;
    }

    /**
     * Casts the values to an std object recursively
     *
     * @param $values
     * @return \stdClass
     */
    protected function objectCast(array $values)
    {
        $object = new \stdClass();
        foreach($values as $key => $value)
        {
            if(is_array($value))
            {
                $value = $this->objectCast($value);
            }
            $object->$key = $value;
        }
        return $object;
    }

    /**
     * Method to support flat type casting using the builtin php methods
     *
     * @param $type
     * @param $value
     * @return float|int|string
     */
    protected function flatCast($type, $value)
    {
        if($type === 'string')
        {
            return (string)$value;
        }
        else if($type === 'int' || $type === 'integer')
        {
            return (int)$value;
        }
        else if($type === 'float' || $type === 'double')
        {
            return (double)$value;
        }
        else if($type === 'bool' || $type === 'boolean')
        {
            return filter_var($value,FILTER_VALIDATE_BOOLEAN);
        }
        return $value;
    }
}