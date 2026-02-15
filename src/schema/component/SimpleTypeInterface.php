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
    /// Whether this type is equal to or derived from the indicated type
    public function isEqualToOrDerivedFrom(string $typeXName): bool;

    /**
     * @brief Primitive datatype the present type is uktimately derived from
     *
     * Return `null` if present datatype is `xsd::anySimpleType`.
     */
    public function getPrimitiveType(): ?self;

    /// Get first such facet in closest ancestor, if any
    public function getFacet(string $facetName): ?XsdElement;

    /**
     * @brief Get value of first such HFP `<hasProperty>` element in closest
     * ancestor, if any
     */
    public function getHfpPropValue(string $propName): ?string;

    /// Whether the value space is numeric
    public function isNumeric(): bool;

    /// Whether the value space is made of integers
    public function isIntegral(): bool;
}
