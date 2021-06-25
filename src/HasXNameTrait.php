<?php

namespace alcamo\dom;

use alcamo\xml\XName;

trait HasXNameTrait
{
    public function getXName(): XName
    {
        return new XName($this->namespaceURI, $this->localName);
    }
}
