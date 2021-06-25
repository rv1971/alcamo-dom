<?php

namespace alcamo\dom;

use alcamo\xml\HasXNameInterface;

/// Element class for use in DOMDocument::registerNodeClass().
class Element extends \DOMElement implements
    \IteratorAggregate,
    HasXNameInterface
{
    use HasXNameTrait;
    use Rfc5147Trait;

    public function __toString()
    {
        return $this->textContent;
    }

    public function getIterator()
    {
        return new ChildElementsIterator($this);
    }

    /// Run XPath query with this node as context node.
    public function query(string $expr)
    {
        return $this->ownerDocument->getXPath()->query($expr, $this);
    }

    /// Run and evaluate XPath query with this node as context node.
    public function evaluate(string $expr)
    {
        return $this->ownerDocument->getXPath()->evaluate($expr, $this);
    }
}
