<?php

namespace alcamo\dom\schema\component;

use alcamo\rdfa\HavingRdfaDataInterface;

/**
 * @brief Type definition
 *
 * @date Last reviewed 2025-11-06
 */
interface TypeInterface extends ComponentInterface, HavingRdfaDataInterface
{
    /// Get base type, if any
    public function getBaseType(): ?self;
}
