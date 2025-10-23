<?php

namespace alcamo\dom;

use alcamo\xml\HavingXNameInterface;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Attr extends \DOMAttr implements
    HavingXNameInterface,
    BaseUriInterface,
    Rfc5147Interface
{
    use BaseUriTrait;
    use HavingXNameTrait;
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
