<?php

namespace alcamo\dom\xsd;

use alcamo\decorator\DecoratorTrait;

/// An XSD enumerator.
class Enumerator implements \IteratorAggregate
{
    use DecoratorTrait;

    public function __toString()
    {
        return $this->handler_->value;
    }
}
