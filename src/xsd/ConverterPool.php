<?php

namespace alcamo\dom\xsd;

use alcamo\dom\ConverterPool as BaseConverterPool;

class ConverterPool extends BaseConverterPool
{
    // Return -1 for `unbounded`.
    public static function toAllNNI($value): int
    {
        return $value == 'unbounded' ? -1 : (int)$value;
    }
}
