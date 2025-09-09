<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Enumeration type definition
 */
interface EnumerationTypeInterface extends SimpleTypeInterface
{
    /// Map of enumerator strings to DOM node objects
    public function getEnumerators(): array;
}
