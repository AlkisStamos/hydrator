<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alks\Hydrator\Annotation;
/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Overrides the default Metadata\Property annotation to provide extra attributes
 *
 * @Annotation
 */
class Property extends \Alks\Metadata\Annotation\Property
{
    /**
     * The name of the property in the source array
     *
     * @var string|null
     */
    protected $from = null;
    /**
     * The name of the property in the extracted array
     *
     * @var string|null
     */
    protected $to = null;

    /**
     * Property constructor.
     * If from or to properties are not null set them as attributes to the parent attrs array
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (isset($data['from'])) {
            $data['attrs']['source_name'] = $data['from'];
            unset($data['from']);
        }
        if (isset($data['to'])) {
            $data['attrs']['extract_name'] = $data['to'];
            unset($data['to']);
        }
        parent::__construct($data);
    }
}