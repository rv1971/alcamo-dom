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
     * @brief Map of attribute namespaces to maps of attribute local names to
     * converters
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
     * @brief Map of element namespaces to maps of element local names to maps
     * of attribute local names to converters
     *
     * The element local name `*` matches all elements in that namespace that
     * are not explicitly listed.
     */
    public const ELEMENT_ATTR_CONVERTERS = [
        self::XH_NS => [
            '*' => [
                'about'             => CP::class . '::uriOrSafeCurieToUri',
                'content'           => CP::class . '::toRdfaDatatype',
                'datatype'          => CP::class . '::curieToUri',
                'property'          => CP::class . '::curieToUri',
                'rel'               => CP::class . '::xhRelToUri',
                'resource'          => CP::class . '::uriOrSafeCurieToUri',
                'rev'               => CP::class . '::xhRelToUri',
                'typeof'            => CP::class . '::xhRelToUri'
            ]
        ],
        self::XSD_NS => [
            'fractionDigits' => [ 'fixed' => CP::class . '::toBool' ],
            'length'         => [ 'fixed' => CP::class . '::toBool' ],
            'maxExclusive'   => [ 'fixed' => CP::class . '::toBool' ],
            'maxInclusive'   => [ 'fixed' => CP::class . '::toBool' ],
            'maxLength'      => [ 'fixed' => CP::class . '::toBool' ],
            'minExclusive'   => [ 'fixed' => CP::class . '::toBool' ],
            'minInclusive'   => [ 'fixed' => CP::class . '::toBool' ],
            'minLength'      => [ 'fixed' => CP::class . '::toBool' ],
            'totalDigits'    => [ 'fixed' => CP::class . '::toBool' ],
            'whiteSpace'     => [ 'fixed' => CP::class . '::toBool' ],
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
        ],
        self::XSL_NS => [
            '*' => [
                'disable-output-escaping'    => CP::class . '::yesNoToBool',
                'elements'                   => CP::class . '::toArray',
                'extension-element-prefixes' => CP::class . '::toArray',
                'exclude-result-prefixes'    => CP::class . '::toArray',
                'href'                       => CP::class . '::toUri',
                'indent'                     => CP::class . '::yesNoToBool',
                'lang'                       => CP::class . '::toLang',
                'media-type'                 => CP::class . '::toMediaType',
                'omit-xml-declaration'       => CP::class . '::yesNoToBool',
                'standalone'                 => CP::class . '::yesNoToBool',
                'terminate'                  => CP::class . '::yesNoToBool',
                'use-attribute-sets'         => CP::class . '::toArray'
            ]
        ]
    ];

    private $value_;

    /// Call createValue() and cache the result
    public function getValue()
    {
        /** Call alcamo::dom::extended::RegisteredNodeTrait::register(). See
         *  alcamo::dom::extended::RegisteredNodeTrait for an explanation why
         *  this is necessary. */
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

        /** - Otherwise, return $value unchanged. */
        return $this->value;
    }
}
