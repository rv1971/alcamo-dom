<?php

namespace alcamo\dom\xsl;

use alcamo\dom\ConverterPool;
use alcamo\dom\extended\Attr as BaseAttr;

class Attr extends BaseAttr
{
    public const XSL_NS = Document::NS['xsl'];

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

    protected function createValue()
    {
        if (
            $this->parentNode->namespaceURI == self::XSL_NS
            && !isset($this->namespaceURI)
        ) {
            $converter = static::XSL_CONVERTERS[$this->localName] ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        return parent::createValue();
    }
}
