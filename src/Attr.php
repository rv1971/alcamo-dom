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

    /**
     * @brief Return the attribute value appropriately converted
     *
     * This implementation simply returns the string content. It is meant to
     * be overriden by more sophisticated methods in derived classes.
     */
    public function getValue()
    {
        return $this->value;
    }
}
