<?php

namespace alcamo\dom;

use alcamo\iterator\IteratorCurrentTrait;

/**
 * @brief Iterator that walks through child elements
 *
 * Skips children which are not elements, such as text nodes and comments. The
 * iteration key is a position counter starting at 0.
 *
 * @date Last reviewed 2021-06-30
 */
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
        // skip children wich are not element nodes
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
        // skip children wich are not element nodes
        for (
            $this->current_ = $this->current_->nextSibling;
            isset($this->current_)
                && $this->current_->nodeType != XML_ELEMENT_NODE;
            $this->current_ = $this->current_->nextSibling
        );

        $this->currentKey_++;
    }
}
