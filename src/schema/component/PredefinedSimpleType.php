<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Type definition predefined in the XML %Schema specification
 *
 * @date Last reviewed 2021-07-09
 */
class PredefinedSimpleType extends PredefinedType implements
    SimpleTypeInterface
{
    public function getFacetValue(string $facetName)
    {
        return null;
    }
}
