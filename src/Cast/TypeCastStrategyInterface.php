<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Hydrator\Cast;

use Alks\Metadata\Metadata\TypeMetadata;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Casts custom and flat types according to a strategy
 */
interface TypeCastStrategyInterface
{
    /**
     * Defines the name of the strategy the type casting should run. If the method returns null the type cast should
     * be used when no strategy is defined or as a fallback.
     *
     * @return null|string
     */
    public function strategy(): ?string;

    /**
     * Returns if the strategy supports the typemetadata
     *
     * @param TypeMetadata $metadata
     * @return bool
     */
    public function isSupported(TypeMetadata $metadata): bool;

    /**
     * Runs the type cast methods on extract direction (when an object is being converted to an array) for the allowed
     * strategy
     *
     * @param TypeMetadata $metadata
     * @param $value
     * @return mixed
     */
    public function extract(TypeMetadata $metadata, $value);

    /**
     * Runs the type cast methods on hydration direction (when an array is being converted to an object) for the allowed
     * strategy
     *
     * @param TypeMetadata $metadata
     * @param $value
     * @return mixed
     */
    public function hydrate(TypeMetadata $metadata, $value);
}