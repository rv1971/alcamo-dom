<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\Document;

/**
 * @brief Type definition predefined in the XML %Schema specification
 *
 * @date Last reviewed 2021-07-09
 */
class PredefinedSimpleType extends PredefinedType implements
    SimpleTypeInterface
{
    public function isEqualToOrDerivedFrom(string $xName): bool
    {
        for (
            $currentType = $this;
            isset($currentType);
            $currentType = $currentType->getBaseType()
        ) {
            if ($currentType->getXName() == $xName) {
                return true;
            }
        }

        return false;
    }
    public function getFacetValue(string $facetName)
    {
        return null;
    }

    public function getHfpPropValue(string $propName): ?string
    {
        return null;
    }

    public function isNumeric(): bool
    {
        return false;
    }
}
