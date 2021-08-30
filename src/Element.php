<?php

namespace alcamo\dom;

use alcamo\xml\HasXNameInterface;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-06-30
 */
class Element extends \DOMElement implements
    \IteratorAggregate,
    HasXNameInterface,
    BaseUriInterface
{
    use HasXNameTrait;
    use Rfc5147Trait;
    use BaseUriTrait;

    /// Return [textContent](https://www.php.net/manual/en/class.domnode#domnode.props.textcontent)
    public function __toString(): string
    {
        return $this->textContent;
    }

    /// Return a ChildElementsIterator on this element
    public function getIterator()
    {
        return new ChildElementsIterator($this);
    }

    /// Run XPath query with this node as the context node
    public function query(string $expr)
    {
        return $this->ownerDocument->getXPath()->query($expr, $this);
    }

    /// Run and evaluate XPath query with this node as the context node
    public function evaluate(string $expr)
    {
        return $this->ownerDocument->getXPath()->evaluate($expr, $this);
    }
}
