<?php

namespace alcamo\dom;

use alcamo\rdfa\{HavingLangInterface, Lang};

/**
 * @brief Text class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Text extends \DOMText implements DomNodeInterface, HavingLangInterface
{
    use DomNodeTrait;

    /// Return [wholeText](https://www.php.net/manual/en/class.domtext#domtext.props.wholetext)
    public function __toString(): string
    {
        return $this->wholeText;
    }

    public function getLang(): ?Lang
    {
        return $this->parentNode->getLang();
    }
}
