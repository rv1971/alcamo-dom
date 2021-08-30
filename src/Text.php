<?php

namespace alcamo\dom;

/**
 * @brief Text class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-06-30
 */
class Text extends \DOMText implements BaseUriInterface
{
    use Rfc5147Trait;
    use BaseUriTrait;

    /// Return [wholeText](https://www.php.net/manual/en/class.domtext#domtext.props.wholetext)
    public function __toString(): string
    {
        return $this->wholeText;
    }
}
