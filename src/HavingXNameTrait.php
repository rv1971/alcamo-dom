<?php

namespace alcamo\dom;

use alcamo\xml\XName;

/**
 * @brief Implement HavingXNameInterface
 *
 * @date Last reviewed 2025-10-23
 */
trait HavingXNameTrait
{
    /// Return node name as expanded name made of namespace URI and local name
    public function getXName(): XName
    {
        return new XName($this->namespaceURI, $this->localName);
    }
}
