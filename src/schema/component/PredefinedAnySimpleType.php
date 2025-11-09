<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @brief Type definition predefined in the XML Schema specification
 *
 * @date Last reviewed 2025-11-06
 */
class PredefinedAnySimpleType extends AbstractPredefinedComponent implements
    SimpleTypeInterface
{
    private $baseType_; ///< ?PredefinedType

    public function __construct(
        Schema $schema,
        ComplexType $baseType
    ) {
        parent::__construct($schema, new XName(self::XSD_NS, 'anySimpleType'));

        $this->baseType_ = $baseType;
    }

   /** @copydoc
    *  alcamo::dom::schema::component::TypeInterface::getBaseType()
    */
    public function getBaseType(): ComplexType
    {
        return $this->baseType_;
    }

    public function isEqualToOrDerivedFrom(string $typeXName): bool
    {
        return $typeXName == $this->getXName()
            || $typeXName == $this->baseType_->getXName();
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getFacet()
     *
     * @return Always `null` since `anySimpleType` type has no facets.
     */
    public function getFacet(string $facetName): ?XsdElement
    {
        return null;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getHfpPropValue()
     *
     * @return Always `null` since `anySimpleType` type has no properties.
     */
    public function getHfpPropValue(string $propName): ?string
    {
        return null;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::isNumeric()
     *
     * @return Always `false` since `anySimpleType` is not numeric.
     */
    public function isNumeric(): bool
    {
        return false;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::isIntegral()
     *
     * @return Always `false` since `anySimpleType` is not made of integers.
     */
    public function isIntegral(): bool
    {
        return false;
    }
}
