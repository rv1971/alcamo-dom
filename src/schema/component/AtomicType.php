<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Atomic type definition
 *
 * @date Last reviewed 2025-11-06
 */
class AtomicType extends AbstractSimpleType
{
    private $isNumeric_; ///< ?bool

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getHfpPropValue()
     */
    public function getHfpPropValue(string $propName): ?string
    {
        for (
            $type = $this;
            $type instanceof self;
            $type = $type->getBaseType()
        ) {
            $propValue = $type->xsdElement_->query(
                "xsd:annotation/xsd:appinfo/hfp:hasProperty[@name = '$propName']/@value"
            )[0];

            if (isset($propValue)) {
                return $propValue->value;
            }
        }

        return null;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::isNumeric()
     */
    public function isNumeric(): bool
    {
        if (!isset($this->isNumeric_)) {
            $this->isNumeric_ = $this->getHfpPropValue('numeric') == 'true';
        }

        return $this->isNumeric_;
    }
}
