<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Attr as BaseAttr, ConverterPool as CP};
use alcamo\ietf\Lang;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-07-01
 */
class Attr extends BaseAttr
{
    use RegisteredNodeTrait;

    /// Map of attr NSs to maps of attr local names to converters
    public const ATTR_CONVERTERS = [
        Document::XML_NS => [
            'lang'                      => CP::class . '::toLang'
        ],
        Document::XSI_NS => [
            'nil'                       => CP::class . '::toBool',
            'noNamespaceSchemaLocation' => CP::class . '::toUri',
            'schemaLocation'            => CP::class . '::pairsToMap',
            'type'                      => CP::class . '::toXName'
        ]
    ];

    private $value_;

    /// Call createValue() and cache the result
    public function getValue()
    {
        /** Call RegisteredNodeTrait::register(). See RegisteredNodeTrait for
         *  explanation why this is necessary.  */
        if (!isset($this->value_)) {
            $this->value_ = $this->createValue();
            $this->register();
        }

        return $this->value_;
    }

    /// Create a value for use in getValue()
    protected function createValue()
    {
        if (isset(static::ATTR_CONVERTERS[$this->namespaceURI])) {
            $converter =
                static::ATTR_CONVERTERS[$this->namespaceURI][$this->localName]
                ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        /** Return values of any other attribute unchanged. */
        return $this->value;
    }
}
