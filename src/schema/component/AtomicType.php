<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Atomic type definition
 */
class AtomicType extends AbstractSimpleType
{
    private $isNumeric_; ///< ?bool

    public function getHfpPropValue(string $propName): ?string
    {
        for (
            $type = $this;
            $type instanceof AbstractXsdComponent;
            $type = $type->getBaseType()
        ) {
            $propValue = $type->getXsdElement()
                ->query("xsd:annotation/xsd:appinfo/hfp:hasProperty[@name = '$propName']/@value")[0];

            if (isset($propValue)) {
                return (string)$propValue;
            }
        }

        return null;
    }

    public function isNumeric(): bool
    {
        if (!isset($this->isNumeric_)) {
            $this->isNumeric_ = $this->getHfpPropValue('numeric') == 'true';
        }

        return $this->isNumeric_;
    }
}
