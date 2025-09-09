<?php

namespace alcamo\dom\xsd;

use alcamo\dom\decorated\Element;

/**
 * @brief Decorator for an `xsd:enumeration` element
 */
class Enumerator extends Decorator
{
    /// Return the content of the `value` attribute
    public function __toString(): string
    {
        return $this->handler_->value;
    }
}
