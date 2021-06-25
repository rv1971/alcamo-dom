<?php

namespace alcamo\dom\xsd;

use alcamo\dom\extended\Attr as BaseAttr;

class Attr extends BaseAttr
{
    public const XSD_NS = Document::NS['xsd'];

    /*
     * `namespace` and `targetNamespace` are *not* modeled as URIs since this
     * would remove, for instance, the trailing `#` in
     * http://www.w3.org/2000/01/rdf-schema#.
     */
    public const XSD_CONVERTERS = [
        'maxOccurs'         => ConverterPool::class . '::toAllNNI',

        'abstract'          => ConverterPool::class . '::toBool',
        'mixed'             => ConverterPool::class . '::toBool',
        'nillable'          => ConverterPool::class . '::toBool',

        'minOccurs'         => ConverterPool::class . '::toInt',

        'schemaLocation'    => ConverterPool::class . '::toUri',
        'source'            => ConverterPool::class . '::toUri',
        'system'            => ConverterPool::class . '::toUri',

        'base'              => ConverterPool::class . '::toXName',
        'itemType'          => ConverterPool::class . '::toXName',
        'ref'               => ConverterPool::class . '::toXName',
        'refer'             => ConverterPool::class . '::toXName',
        'substitutionGroup' => ConverterPool::class . '::toXName',
        'type'              => ConverterPool::class . '::toXName',

        'memberTypes'       => ConverterPool::class . '::toXNames'
    ];

    protected function createValue()
    {
        if (
            $this->parentNode->namespaceURI == self::XSD_NS
            && !isset($this->namespaceURI)
        ) {
            $converter = static::XSD_CONVERTERS[$this->localName] ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        return parent::createValue();
    }
}
