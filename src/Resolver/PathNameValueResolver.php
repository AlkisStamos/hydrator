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
 * Resolves the target keys based on the property metadata source and extract names. If the names contain the dot char
 * the resolver will force the hydrator to search in/create nested arrays
 */
class PathNameValueResolver implements PropertyValueResolverInterface
{
    /**
     * Name of the strategy on which the resolver should run
     *
     * @return null|string
     */
    public function strategy(): ?string
    {
        return null;
    }

    /**
     * Checks if the source name in the property metadata is set in order for the resolver to support it
     *
     * @param PropertyMetadata $propertyMetadata
     * @return bool
     */
    public function supports(PropertyMetadata $propertyMetadata): bool
    {
        return isset($propertyMetadata->attrs['source_name']);
    }

    /**
     * Checks if the resolver should run on the hydrator extract method as well
     *
     * @param PropertyMetadata $propertyMetadata
     * @return bool
     */
    public function supportsExtraction(PropertyMetadata $propertyMetadata): bool
    {
        return isset($propertyMetadata->attrs['extract_name']);
    }

    /**
     * Resolves the key of the target array on where the data would reside for the target property
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array $target
     * @return string
     */
    public function resolveProperty(PropertyMetadata $propertyMetadata, array $target)
    {
        return $propertyMetadata->attrs['source_name'];
    }

    /**
     * Resolves the key point of the extracted array where the hydrator will extract the data of the target property
     *
     * @param PropertyMetadata $propertyMetadata
     * @param $object
     * @return string
     */
    public function extractProperty(PropertyMetadata $propertyMetadata, $object)
    {
        return $propertyMetadata->attrs['extract_name'];
    }
}