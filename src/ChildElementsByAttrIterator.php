<?php

namespace alcamo\dom;

use alcamo\iterator\IteratorCurrentTrait;
use alcamo\xml\XName;

/**
 * @brief Iterator that walks through child elements indexed by an attribute
 *
 * Skips children which are not elements, such as text nodes and comments.
 *
 * @date Last reviewed 2025-10-27
 */
class ChildElementsByAttrIterator extends ChildElementsIterator implements
    \Iterator
{
    private $attrNsName_;    ///< ?string
    private $attrLocalName_; ///< string

    /**
     * @param $parentNode Node whose child elements are to be iterated.
     *
     * @param $attrName XName|string Attribute to use as iteration key.
     */
    public function __construct(\DOMNode $parentNode, $attrName)
    {
        parent::__construct($parentNode);

        if ($attrName instanceof XName) {
            [ $this->attrNsName_, $this->attrLocalName_ ] =
                $attrName->getPair();
        } else {
            $this->attrLocalName_ = $attrName;
        }
    }

    public function key()
    {
        return $this->current()
            ->getAttributeNS($this->attrNsName_, $this->attrLocalName_);
    }
}
