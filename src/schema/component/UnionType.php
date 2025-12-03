<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Union type definition
 *
 * Note that UnionType is not derived from AtomicType because its member types
 * might contain a list type, in which case the union would not be atomic.
 *
 * @date Last reviewed 2025-11-06
 */
class UnionType extends AbstractSimpleType
{
    protected $memberTypes_; ///< Array of SimpleTypeInterface

    private const NON_ID_ATTR_COUNT = 'count(@*[name() != "id"])';

    private $isNumeric_;  ///< bool
    private $isIntegral_; ///< bool

    /** @param $memberTypes array of SimpleTypeInterface */
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        array $memberTypes,
        ?SimpleTypeInterface $baseType = null
    ) {
        parent::__construct($schema, $xsdElement, $baseType);

        $this->memberTypes_ = $memberTypes;
    }

    /// Get map of type XName to SimpleTypeInterface
    public function getMemberTypes(): array
    {
        return $this->memberTypes_;
    }

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::getFacet()
     *
     * @return The first facet element encountered if all member types have
     * this facet with the same attributes.
     */
    public function getFacet(string $facetName): ?XsdElement
    {
        $commonFacet = null;

        foreach ($this->memberTypes_ as $memberType) {
            $facet = $memberType->getFacet($facetName);

            if (!isset($facet)) {
                return null;
            }

            if (isset($commonFacet)) {
                if ($facet->evaluate(self::NON_ID_ATTR_COUNT) != $attrCount) {
                    return null;
                }

                foreach ($facet->attributes as $name => $attr) {
                    if ($name != 'id' && $attr != $commonFacet->$name) {
                        return null;
                    }
                }
            } else {
                $commonFacet = $facet;
                $attrCount = $commonFacet->evaluate(self::NON_ID_ATTR_COUNT);
            }
        }

        return $commonFacet;
    }

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::getHfpPropValue()
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
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::isNumeric()
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

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::isIntegral()
     *
     * @return `true` if all member types are integral.
     */
    public function isIntegral(): bool
    {
        if (!isset($this->isIntegral_)) {
            $this->isIntegral_ = true;

            foreach ($this->memberTypes_ as $memberType) {
                if (!$memberType->isIntegral()) {
                    $this->isIntegral_ = false;
                    break;
                }
            }
        }

        return $this->isIntegral_;
    }
}
