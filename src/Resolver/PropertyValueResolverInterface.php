<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata\Resolver;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Resolves where the data reside in the target or extracted arrays
 */
interface PropertyValueResolverInterface
{
    /**
     * Name of the strategy on which the resolver should run
     *
     * @return null|string
     */
    public function strategy(): ?string;

    /**
     * Checks if the resolver supports the target property metadata
     *
     * @param PropertyMetadata $propertyMetadata
     * @return bool
     */
    public function supports(PropertyMetadata $propertyMetadata): bool;

    /**
     * Checks if the resolver should run on the hydrator extract method as well
     *
     * @param PropertyMetadata $propertyMetadata
     * @return bool
     */
    public function supportsExtraction(PropertyMetadata $propertyMetadata): bool;

    /**
     * Resolves the key of the target array on where the data would reside for the target property.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array $target
     * @return string
     */
    public function resolveProperty(PropertyMetadata $propertyMetadata, array $target);

    /**
     * Resolves the key point of the extracted array where the hydrator will extract the data of the target property
     *
     * @param PropertyMetadata $propertyMetadata
     * @param $object
     * @return string
     */
    public function extractProperty(PropertyMetadata $propertyMetadata, $object);
}