<?php

namespace alcamo\dom\xsd;

use alcamo\dom\ConverterPool as BaseConverterPool;

/**
 * @brief XSD-specific converter functions for DOM node values
 *
 * @date Last reviewed 2021-07-09
 */
class ConverterPool extends BaseConverterPool
{
    // Return -1 for `unbounded`
    public static function toAllNNI($value): int
    {
        return $value == 'unbounded' ? -1 : (int)$value;
    }
}
