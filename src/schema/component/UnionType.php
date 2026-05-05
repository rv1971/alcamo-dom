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
    private $isSigned_; ///< bool

    /** @param $memberTypes array of SimpleTypeInterface */
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        array $memberTypes
    ) {
        /* Map the index of each member type to the array of its base types in
         * reverse order, i.e. starting with the ultimate base type. */
        $baseTypeMap = [];

        foreach ($memberTypes as $memberType) {
            $baseTypes = [];

            foreach (
                $memberType
                    ->getSelfAndBaseTypes(AbstractSimpleType::class) as $baseType
            ) {
                $baseTypes[] = $baseType;
            }

            $baseTypeMap[] = array_reverse($baseTypes);
        }

        /* Find the most derived type that appears in all lists of base types
         * of member types, if any. */

        $commonBaseType = null;

        for ($i = 0; isset($baseTypeMap[0][$i]); $i++) {
            $baseType = $baseTypeMap[0][$i];

            for ($j = 1; isset($baseTypeMap[$j]); $j++) {
                if (
                    !isset($baseTypeMap[$j][$i])
                        || $baseTypeMap[$j][$i] !== $baseType
                ) {
                    break 2;
                }
            }

            $commonBaseType = $baseType;
        }

        parent::__construct($schema, $xsdElement, $commonBaseType);

        $this->memberTypes_ = $memberTypes;
    }

    /// Get map of type XName to SimpleTypeInterface
    public function getMemberTypes(): array
    {
        return $this->memberTypes_;
    }

    public function getPrimitiveType(): ?SimpleTypeInterface
    {
        return isset($this->baseType_)
            ? $this->baseType_->getPrimitiveType()
            : null;
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

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::isSigned()
     *
     * @return `true` if all member types are numeric and at least one is
     * signed.
     */
    public function isSigned(): bool
    {
        if (!isset($this->isSigned_)) {
            $this->isSigned_ = false;

            if ($this->isNumeric()) {
                foreach ($this->memberTypes_ as $memberType) {
                    if ($memberType->isSigned()) {
                        $this->isSigned_ = true;
                        break;
                    }
                }
            }
        }

        return $this->isSigned_;
    }

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::isPrintable()
     *
     * @return `true` if all member types are printable.
     */
    public function isPrintable(): bool
    {
        if (!isset($this->isPrintable_)) {
            $this->isPrintable_ = true;

            foreach ($this->memberTypes_ as $memberType) {
                if (!$memberType->isPrintable()) {
                    $this->isPrintable_ = false;
                    break;
                }
            }
        }

        return $this->isPrintable_;
    }
}
