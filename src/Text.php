<?php

namespace alcamo\dom;

/**
 * @brief Text class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Text extends \DOMText implements HavingBaseUriInterface, Rfc5147Interface
{
    use HavingBaseUriTrait;
    use Rfc5147Trait;

    /// Return [wholeText](https://www.php.net/manual/en/class.domtext#domtext.props.wholetext)
    public function __toString(): string
    {
        return $this->wholeText;
    }
}
