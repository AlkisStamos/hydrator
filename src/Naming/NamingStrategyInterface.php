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
 * Applies naming rules to metadata
 */
interface NamingStrategyInterface
{
    /**
     * Translates the property name from its default name to the name that will be indexed when extracted
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function translatePropertyName(PropertyMetadata $propertyMetadata): string;

    /**
     * Generates the method setter name by the property name
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function setterName(PropertyMetadata $propertyMetadata): string;

    /**
     * Generates the method getter name by the property name
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    public function getterName(PropertyMetadata $propertyMetadata): string;
}