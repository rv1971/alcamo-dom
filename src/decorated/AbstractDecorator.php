<?php

namespace alcamo\dom\decorated;

use alcamo\decorator\DecoratorTrait;

/**
 * @brief Decorator for Element objects
 */
abstract class AbstractDecorator implements
    \Countable,
    \IteratorAggregate,
    \Arrayaccess
{
    use DecoratorTrait;

    public function __construct(Element $element)
    {
        $this->handler_ = $element;
    }

    public function __toString(): string
    {
        return (string)$this->handler_;
    }

    public function getElement(): Element
    {
        return $this->handler_;
    }
}
