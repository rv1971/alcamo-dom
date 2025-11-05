<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Attr as BaseAttr, ConverterPool as CP};
use alcamo\rdfa\Lang;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Attr extends BaseAttr
{
    use RegisteredNodeTrait;

    /**
     * @brief Map of attr NSs to maps of attr local names to converters
     *
     * @sa [Use of CURIEs in Specific Attributes](https://www.w3.org/TR/rdfa-syntax/#sec_5.4.4.)
     */
    public const ATTR_CONVERTERS = [
        self::OWL_NS => [
            'sameAs'                    => CP::class . '::toUri'
        ],
        self::XML_NS => [
            'lang'                      => CP::class . '::toLang'
        ],
        self::XSI_NS => [
            'nil'                       => CP::class . '::toBool',
            'noNamespaceSchemaLocation' => CP::class . '::toUri',
            'schemaLocation'            => CP::class . '::pairsToMap',
            'type'                      => CP::class . '::toXName'
        ]
    ];

    /**
     * @brief Map of element NSs to maps of element local names to maps of
     * attr local names to converters
     */
    public const ELEMENT_ATTR_CONVERTERS = [
        self::XH_NS => [
            '*' => [
                'about'             => CP::class . '::uriOrSafeCurieToUri',
                'datatype'          => CP::class . '::curieToUri',
                'property'          => CP::class . '::curieToUri',
                'rel'               => CP::class . '::xhRelToUri',
                'resource'          => CP::class . '::uriOrSafeCurieToUri',
                'rev'               => CP::class . '::xhRelToUri',
                'typeof'            => CP::class . '::xhRelToUri'
            ]
        ],
        self::XSD_NS => [
            '*' => [
                'maxOccurs'         => CP::class . '::toAllNNI',

                'abstract'          => CP::class . '::toBool',
                'mixed'             => CP::class . '::toBool',
                'nillable'          => CP::class . '::toBool',

                'minOccurs'         => CP::class . '::toInt',

                'schemaLocation'    => CP::class . '::toUri',
                'source'            => CP::class . '::toUri',
                'system'            => CP::class . '::toUri',

                'base'              => CP::class . '::toXName',
                'itemType'          => CP::class . '::toXName',
                'ref'               => CP::class . '::toXName',
                'refer'             => CP::class . '::toXName',
                'substitutionGroup' => CP::class . '::toXName',
                'type'              => CP::class . '::toXName',

                'memberTypes'       => CP::class . '::toXNames'
            ]
        ]
    ];

    private $value_;

    /// Call createValue() and cache the result
    public function getValue()
    {
        /** Call alcamo::dom::extended::RegisteredNodeTrait::register(). See
         *  RegisteredNodeTrait for explanation why this is necessary.  */
        if (!isset($this->value_)) {
            $this->value_ = $this->createValue();
            $this->register();
        }

        return $this->value_;
    }

    /// Create a value for use in getValue()
    protected function createValue()
    {
        /** - Use the converter in ATTR_CONVERTERS, if present. */
        if (isset(static::ATTR_CONVERTERS[$this->namespaceURI])) {
            $converter =
                static::ATTR_CONVERTERS[$this->namespaceURI][$this->localName]
                ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        /** - Otherwise, for attributes without namespace, use the converter
         * in ELEMENT_ATTR_CONVERTERS, if present. */
        if (!isset($this->namespaceURI)) {
            $converterMaps =
                static::ELEMENT_ATTR_CONVERTERS[$this->parentNode->namespaceURI]
            ?? null;

            if (isset($converterMaps)) {
                $converterMap = $converterMaps[$this->parentNode->localName]
                ?? $converterMaps['*']
                ?? null;

                if (isset($converterMap)) {
                    $converter = $converterMap[$this->localName] ?? null;

                    if (isset($converter)) {
                        return $converter($this->value, $this);
                    }
                }
            }
        }

        /** - Otherwise, return value unchanged. */
        return $this->value;
    }
}
