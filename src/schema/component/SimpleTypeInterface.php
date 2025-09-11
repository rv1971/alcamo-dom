<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Simple type definition
 */
interface SimpleTypeInterface extends TypeInterface
{
    /// Value of first facet in closest ancestor, if any
    public function getFacetValue(string $facetName);

    public function isEqualToOrDerivedFrom(string $xName): bool;

    public function getHfpPropValue(string $propName): ?string;

    public function isNumeric(): bool;
}
