<?php

namespace alcamo\dom;

use alcamo\iterator\IteratorCurrentTrait;

/**
 * @brief Iterator that walks through child elements indexed by an attribute
 */
class ChildElementsByAttrIterator implements \Iterator
{
    use IteratorCurrentTrait;

    private $parentElement_;
    private $attrName_;

    public function __construct(\DOMElement $parentElement, string $attrName)
    {
        $this->parentElement_ = $parentElement;
        $this->attrName_ = $attrName;
    }

    public function key()
    {
        return $this->current_->getAttribute($this->attrName_);
    }

    public function rewind()
    {
        // skip children wich are not element nodes
        for (
            $this->current_ = $this->parentElement_->firstChild;
            isset($this->current_)
                && $this->current_->nodeType != XML_ELEMENT_NODE;
            $this->current_ = $this->current_->nextSibling
        );
    }

    public function next()
    {
        // skip children wich are not element nodes
        for (
            $this->current_ = $this->current_->nextSibling;
            isset($this->current_)
                && $this->current_->nodeType != XML_ELEMENT_NODE;
            $this->current_ = $this->current_->nextSibling
        );
    }
}
