<?php

namespace alcamo\dom\xsl;

use alcamo\dom\ConverterPool;
use alcamo\dom\extended\Attr as BaseAttr;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-07-01
 */
class Attr extends BaseAttr
{
    /// Converters for attributes of elements in the @ref XSL_NS namespace
    public const XSL_CONVERTERS = [
        'disable-output-escaping'    => ConverterPool::class . '::yesNoToBool',
        'elements'                   => ConverterPool::class . '::toArray',
        'extension-element-prefixes' => ConverterPool::class . '::toArray',
        'exclude-result-prefixes'    => ConverterPool::class . '::toArray',
        'href'                       => ConverterPool::class . '::toUri',
        'indent'                     => ConverterPool::class . '::yesNoToBool',
        'lang'                       => ConverterPool::class . '::toLang',
        'media-type'                 => ConverterPool::class . '::toMediaType',
        'omit-xml-declaration'       => ConverterPool::class . '::yesNoToBool',
        'standalone'                 => ConverterPool::class . '::yesNoToBool',
        'terminate'                  => ConverterPool::class . '::yesNoToBool',
        'use-attribute-sets'         => ConverterPool::class . '::toArray'
    ];

    /// @copybrief alcamo::dom::Attr::createValue()
    protected function createValue()
    {
        /** Convert values of attributes in the XSL namespace using @ref
         *  XSL_CONVERTERS. */
        if (
            !isset($this->namespaceURI)
            && $this->parentNode->namespaceURI == self::XSL_NS
        ) {
            $converter = static::XSL_CONVERTERS[$this->localName] ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        return parent::createValue();
    }
}
