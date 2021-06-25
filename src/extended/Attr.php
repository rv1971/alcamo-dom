<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Attr as BaseAttr, ConverterPool};

class Attr extends BaseAttr
{
    use RegisteredNodeTrait;

    public const XSI_NS = Document::NS['xsi'];

    public const XSI_CONVERTERS = [
        'nil'                       => ConverterPool::class . '::toBool',
        'noNamespaceSchemaLocation' => ConverterPool::class . '::toUri',
        'schemaLocation'            => ConverterPool::class . '::pairsToMap',
        'type'                      => ConverterPool::class . '::toXName'
    ];

    private $value_;

    public function getValue()
    {
        if (!isset($this->value_)) {
            $this->value_ = $this->createValue();
            $this->register();
        }

        return $this->value_;
    }

    /// To be redefined in child classes with something more sophisticated
    protected function createValue()
    {
        if ($this->namespaceURI == self::XSI_NS) {
            $converter = static::XSI_CONVERTERS[$this->localName] ?? null;

            if (isset($converter)) {
                return $converter($this->value, $this);
            }
        }

        return $this->value;
    }
}
