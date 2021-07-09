<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Type definition
 *
 * @date Last reviewed 2021-07-09
 */
interface TypeInterface extends ComponentInterface
{
    /// Base type, if any
    public function getBaseType(): ?self;
}
