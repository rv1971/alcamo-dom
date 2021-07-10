<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Attribute declaration
 */
interface AttrInterface extends ComponentInterface
{
    public function getType(): SimpleTypeInterface;
}
