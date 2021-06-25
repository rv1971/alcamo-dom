<?php

namespace alcamo\dom;

use alcamo\iterator\IteratorCurrentTrait;

class ChildElementsIterator implements \Iterator
{
    use IteratorCurrentTrait;

    private $parentElement_;

    public function __construct(\DOMElement $parentElement)
    {
        $this->parentElement_ = $parentElement;
    }

    public function rewind()
    {
        /** Skip children wich are not element nodes. */
        for (
            $this->current_ = $this->parentElement_->firstChild;
            isset($this->current_)
                && $this->current_->nodeType != XML_ELEMENT_NODE;
            $this->current_ = $this->current_->nextSibling
        );

        $this->currentKey_ = 0;
    }

    public function next()
    {
        /** Skip children wich are not element nodes. */
        for (
            $this->current_ = $this->current_->nextSibling;
            isset($this->current_)
                && $this->current_->nodeType != XML_ELEMENT_NODE;
            $this->current_ = $this->current_->nextSibling
        );

        $this->currentKey_++;
    }
}
