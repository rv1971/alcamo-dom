<?php

namespace alcamo\dom\schema\component;

use alcamo\rdfa\{HavingRdfaDataInterface, RdfaData};

/**
 * @brief Type definition
 *
 * @date Last reviewed 2025-11-06
 */
interface TypeInterface extends ComponentInterface, HavingRdfaDataInterface
{
    /// Get base type, if any
    public function getBaseType(): ?self;

    /// Collect RdfaData in this type and its base types
    public function getRdfaData(): ?RdfaData;
}
