<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Simple type definition
 */
interface SimpleTypeInterface extends TypeInterface
{
    public function isEqualToOrDerivedFrom(string $xName): bool;

    /// Value of first facet in closest ancestor, if any
    public function getFacetValue(string $facetName): ?string;

    public function getHfpPropValue(string $propName): ?string;

    public function isNumeric(): bool;
}
