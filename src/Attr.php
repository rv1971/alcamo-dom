<?php

namespace alcamo\dom;

use alcamo\xml\HasXNameInterface;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-06-30
 */
class Attr extends \DOMAttr implements HasXNameInterface, BaseUriInterface
{
    use HasXNameTrait;
    use Rfc5147Trait;
    use BaseUriTrait;

    /// Return attribute value
    public function __toString()
    {
        return $this->value;
    }

    /// Return attribute value; meant to be overridden in derived classes
    public function getValue()
    {
        return $this->value;
    }
}
