<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Enumeration type definition
 *
 * @date Last reviewed 2021-07-09
 */
interface EnumerationTypeInterface extends SimpleTypeInterface
{
    /// Map of enumerator strings to Enumerator objects
    public function getEnumerators(): array;
}
