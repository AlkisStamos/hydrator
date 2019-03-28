<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Metadata;
use AlkisStamos\Metadata\Metadata\ClassMetadata;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Hooks in various stages in hydration/extraction lifecycle
 */
interface HydratorHookInterface
{
    /**
     * Restricts the hook to run only on certain hydration strategy
     *
     * @return null|string
     */
    public function strategy(): ?string;

    /**
     * Runs before the hydration process
     *
     * @param ClassMetadata $metadata
     * @param array $data
     */
    public function onBeforeHydrate(ClassMetadata $metadata, array $data): void;

    /**
     * Runs as soon as the hydration process is completed
     *
     * @param ClassMetadata $metadata
     * @param $instance
     */
    public function onAfterHydrate(ClassMetadata $metadata, $instance): void;

    /**
     * Runs before the extraction process
     *
     * @param ClassMetadata $metadata
     * @param $instance
     */
    public function onBeforeExtract(ClassMetadata $metadata, $instance): void;

    /**
     * Runs as soon as the extaction process is completed
     *
     * @param ClassMetadata $metadata
     * @param array $data
     */
    public function onAfterExtract(ClassMetadata $metadata, array $data): void;

    /**
     * Runs as before the hydrator resolves the target data for a property. If the preferValue reference is set to true
     * inside the hook then the hydrator should set the property with anything this method returns instead of the target
     * data
     *
     * @param ClassMetadata $classMetadata
     * @param PropertyMetadata $propertyMetadata
     * @param $targetData
     * @param bool $preferValue
     * @return mixed
     */
    public function onPropertyHydrate(ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata, $targetData, bool &$preferValue=false);

    /**
     * Runs as soon as the hydrator resolves the content to extract for a property. If the preferValue reference is set
     * to true inside the hook then the hydrator should extract the data the hook returns instead of the extracted data.
     *
     * @param ClassMetadata $classMetadata
     * @param PropertyMetadata $propertyMetadata
     * @param $extractedContent
     * @param bool $preferValue
     * @return mixed
     */
    public function onPropertyExtract(ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata, $extractedContent, bool &$preferValue=false);
}