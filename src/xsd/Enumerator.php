<?php

namespace alcamo\dom\xsd;

use alcamo\decorator\DecoratorTrait;
use alcamo\dom\decorated\Element;

/**
 * @brief Decorator for an `xsd:enumeration` element
 *
 * @date Last reviewed 2021-07-09
 */
class Enumerator implements \IteratorAggregate
{
    use DecoratorTrait;

    /// Return underlying \<xsd:enumerator> element
    public function getDomNode(): Element
    {
        return $this->handler_;
    }

    /// Return the content of the `value` attribute
    public function __toString(): string
    {
        return $this->handler_->value;
    }
}
