<?php

namespace alcamo\dom;

use alcamo\rdfa\{HavingLangInterface, Lang};
use alcamo\xml\HavingXNameInterface;

/**
 * @brief Attribute class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Attr extends \DOMAttr implements
    DomNodeInterface,
    HavingLangInterface,
    HavingXNameInterface
{
    use DomNodeTrait;
    use HavingXNameTrait;

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

    public function getLang(): ?Lang
    {
        return $this->parentNode->getLang();
    }
}
