<?php

namespace alcamo\dom\psvi;

use alcamo\dom\extended\Element as BaseElement;
use alcamo\dom\schema\component\TypeInterface;

/**
 * @brief %Element class for use in DOMDocument::registerNodeClass()
 *
 * Provides getType() to retrieve the XSD type of this element.
 *
 * @date Last reviewed 2021-07-11
 */
class Element extends BaseElement
{
    private $type_ = false;  ///< TypeInterface

    public function getType(): TypeInterface
    {
        if ($this->type_ === false) {
            $this->type_ = $this->ownerDocument->getSchema()
                ->lookupElementType($this);
        }

        return $this->type_;
    }
}
