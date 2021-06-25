<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

interface ComponentInterface
{
    public function getSchema(): Schema;

    public function getXName(): ?XName;
}
