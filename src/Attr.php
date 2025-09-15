<?php

namespace alcamo\dom;

use alcamo\xml\HasXNameInterface;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-09-15
 */
class Attr extends \DOMAttr implements HasXNameInterface, BaseUriInterface
{
    use BaseUriTrait;
    use HasXNameTrait;
    use Rfc5147Trait;

    /// Return attribute value
    public function __toString(): string
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
