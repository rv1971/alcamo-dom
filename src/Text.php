<?php

namespace alcamo\dom;

/**
 * @brief Text class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-06-30
 */
class Text extends \DOMText
{
    use Rfc5147Trait;

    /// Return [wholeText](https://www.php.net/manual/en/class.domtext#domtext.props.wholetext)
    public function __toString()
    {
        return $this->wholeText;
    }
}
