<?php

namespace alcamo\dom\xsd;

use alcamo\decorator\DecoratorTrait;

/**
 * @brief Decorator for an `xsd:enumeration` element
 *
 * @date Last reviewed 2021-07-09
 */
class Enumerator implements \IteratorAggregate
{
    use DecoratorTrait;

    /// Return the content of the `value` attribute
    public function __toString()
    {
        return $this->handler_->value;
    }
}
