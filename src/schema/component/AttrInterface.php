<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Attribute declaration
 *
 * @date Last reviewed 2025-11-06
 */
interface AttrInterface extends ComponentInterface
{
    public function getType(): SimpleTypeInterface;
}
