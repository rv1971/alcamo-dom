<?php

namespace alcamo\dom;

/**
 * @brief Comment class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2025-10-23
 */
class Comment extends \DOMComment implements DomNodeInterface
{
    use DomNodeTrait;

    /// Return [textContent](https://www.php.net/manual/de/class.domnode.php#domnode.props.textcontent)
    public function __toString(): string
    {
        return $this->textContent;
    }
}
