<?php

namespace alcamo\dom\decorated;

use alcamo\decorator\DecoratorTrait;

/**
 * @brief Decorator for Element objects
 *
 * @date Last reviewed 2025-10-23
 */
abstract class AbstractElementDecorator implements
    \Countable,
    \IteratorAggregate,
    \Arrayaccess
{
    use DecoratorTrait;

    /// Return DOMElement::textContent
    public function __toString(): string
    {
        return $this->handler_->textContent;
    }

    /// Get the underlying element
    public function getElement(): Element
    {
        return $this->handler_;
    }
}
