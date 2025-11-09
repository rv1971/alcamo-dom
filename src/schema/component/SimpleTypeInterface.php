<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief Simple type definition
 *
 * @date Last reviewed 2025-11-06
 */
interface SimpleTypeInterface extends TypeInterface
{
    public function isEqualToOrDerivedFrom(string $typeXName): bool;

    /// First facet in closest ancestor, if any
    public function getFacet(string $facetName): ?XsdElement;

    /**
     * @brief Value of first hasFacetAndProperty `<hasProperty>` element in
     * closest ancestor, if any
     */
    public function getHfpPropValue(string $propName): ?string;

    /// Whether the value space is numeric
    public function isNumeric(): bool;
}
