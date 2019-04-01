<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Hydrator\Naming;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Translates a property name from camel case to underscore (eg propertyName=>property_name)
 */
class UnderscoreNamingStrategy implements NamingStrategyInterface
{
    /**
     * Translates the property name from its default name to the name that will be indexed when extracted
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function translatePropertyName(PropertyMetadata $propertyMetadata): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $propertyMetadata->name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * Generates the method setter name by the property name
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function setterName(PropertyMetadata $propertyMetadata): string
    {
        return 'set'.ucfirst($propertyMetadata->name);
    }

    /**
     * Generates the method getter name by the property name
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function getterName(PropertyMetadata $propertyMetadata): string
    {
        if($propertyMetadata->type->name === 'bool' || $propertyMetadata->type->name === 'boolean')
        {
            return 'is'.ucfirst($propertyMetadata->name);
        }
        return 'get'.ucfirst($propertyMetadata->name);
    }
}