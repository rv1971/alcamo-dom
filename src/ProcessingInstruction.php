<?php

namespace alcamo\dom;

/**
 * @brief Processing Instruction class for use in
 * DOMDocument::registerNodeClass()
 *
 * Supports iteration of attributes and access to attributes as object
 * properties.
 *
 * @date Last reviewed 2025-10-26
 */
class ProcessingInstruction extends \DOMProcessingInstruction implements
    DomNodeInterface,
    \IteratorAggregate
{
    use DomNodeTrait;

    private $fragment_;   ///< DOMDocumentFragment

    /// Return data
    public function __toString(): string
    {
        return $this->data;
    }

    public function getAttributes(): \DOMNamedNodeMap
    {
        if (!isset($this->fragment_)) {
            $this->fragment_ = $this->ownerDocument->createDocumentFragment();

            $this->fragment_->appendXML("<pi {$this->data}/>");
        }

        return $this->fragment_->firstChild->attributes;
    }

    /**
     * @return Traversable in PHP7, IteratorAggregate in PHP 8
     *
     * @note Values are Attr objects an can be explicitly converted to strings
     */
    public function getIterator()
    {
        return $this->getAttributes();
    }

    public function __isset(string $attrName): bool
    {
        return $this->getAttributes()->getNamedItem($attrName) !== null;
    }

    public function __get(string $attrName): string
    {
        return $this->getAttributes()->getNamedItem($attrName);
    }
}
