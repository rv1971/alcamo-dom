<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Enumeration type definition
 *
 * @date Last reviewed 2025-11-06
 */
interface EnumerationTypeInterface extends SimpleTypeInterface
{
    /// Get map of enumerator strings to DOMElement objects
    public function getEnumerators(): array;
}
