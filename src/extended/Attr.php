<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Attr as BaseAttr, ConverterPool};

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-07-01
 */
class Attr extends BaseAttr
{
    use RegisteredNodeTrait;

    /// Converters for attributes in the @ref alcamo::dom::Document::XSI_NS namespace
    public const XSI_CONVERTERS = [
        'nil'                       => ConverterPool::class . '::toBool',
        'noNamespaceSchemaLocation' => ConverterPool::class . '::toUri',
        'schemaLocation'            => ConverterPool::class . '::pairsToMap',
        'type'                      => ConverterPool::class . '::toXName'
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
        /** Convert values of attributes in the @ref
         *  alcamo::dom::Document::XSI_NS namespace using @ref
         *  XSI_CONVERTERS. */
        if ($this->namespaceURI == Document::XSI_NS) {
            $converter = static::XSI_CONVERTERS[$this->localName] ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        /** Return values of any other attribute unchanged. */
        return $this->value;
    }
}
