<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Simple type definition
 *
 * @date Last reviewed 2021-07-09
 */
interface SimpleTypeInterface extends TypeInterface
{
    /// Value of first facet in closest ancestor, if any
    public function getFacetValue(string $facetName);
}
