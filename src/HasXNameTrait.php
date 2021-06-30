<?php

namespace alcamo\dom;

use alcamo\xml\XName;

/**
 * @brief Provide getXName()
 *
 * @date Last reviewed 2021-06-30
 */
trait HasXNameTrait
{
    /// Return node name as expanded name made of namespaceURI and localName
    public function getXName(): XName
    {
        return new XName($this->namespaceURI, $this->localName);
    }
}
