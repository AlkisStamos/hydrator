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
 * Extracts and hydrates php date time objects
 */
class DateTimeCastStrategy implements TypeCastStrategyInterface
{
    /** @var string The default iso format for date time objects */
    const DEFAULT_FORMAT = 'Y-m-d\TH:i:sO';
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
        return ['DateTime','\DateTime','date_time'];
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
        if($value instanceof \DateTime)
        {
            return $metadata->format === null ? $value->format(self::DEFAULT_FORMAT) : $value->format($metadata->format);
        }
        return null;
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
        return $metadata->format === null ? \DateTime::createFromFormat(self::DEFAULT_FORMAT,$value) : \DateTime::createFromFormat($metadata->format,$value);
    }
}