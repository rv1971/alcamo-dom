<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief Union type definition
 *
 * @date Last reviewed 2025-11-06
 */
class UnionType extends AbstractSimpleType
{
    protected $memberTypes_; ///< Array of SimpleTypeInterface

    private $isNumeric_; ///< ?bool

    /// @param $memberTypes @copybrief getMemberTypes()
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        array $memberTypes,
        ?SimpleTypeInterface $baseType = null
    ) {
        parent::__construct($schema, $xsdElement, $baseType);

        $this->memberTypes_ = $memberTypes;
    }

    /// Array of SimpleTypeInterface
    public function getMemberTypes(): array
    {
        return $this->memberTypes_;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getFacetValue()
     *
     * @return A value if all member types have this facet with the same value.
     */
    public function getFacetValue(string $facetName): ?string
    {
        $uniqueValue = null;

        foreach ($this->memberTypes_ as $memberType) {
            $value = $memberType->getFacetValue($facetName);

            if (
                !isset($value)
                || isset($uniqueValue) && $uniqueValue != $value
            ) {
                return null;
            }

            $uniqueValue = $value;
        }

        return $uniqueValue;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getHfpPropValue()
     *
     * @return A value if all member types have this property with the same
     * value.
     */
    public function getHfpPropValue(string $propName): ?string
    {
        $uniqueValue = null;

        foreach ($this->memberTypes_ as $memberType) {
            $value = $memberType->getHfpPropValue($propName);

            if (
                !isset($value)
                || isset($uniqueValue) && $uniqueValue != $value
            ) {
                return null;
            }

            $uniqueValue = $value;
        }

        return $uniqueValue;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::isNumeric()
     *
     * @return `true` if all member types are numeric.
     */
    public function isNumeric(): bool
    {
        if (!isset($this->isNumeric_)) {
            $this->isNumeric_ = $this->getHfpPropValue('numeric') == 'true';
        }

        return $this->isNumeric_;
    }
}
