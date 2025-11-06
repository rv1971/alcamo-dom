<?php

namespace alcamo\dom\schema\component;

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
    *  alcamo::dom::schema::component::TypeInterface::getBaseType() */
    public function getBaseType(): ComplexType
    {
        return $this->baseType_;
    }

    public function isEqualToOrDerivedFrom(string $xName): bool
    {
        return $xName == $this->getXName()
            || $xName == $this->baseType_->getXName();
    }

    /// Always `null` since `anySimpleType` type has no facets
    public function getFacetValue(string $facetName): ?string
    {
        return null;
    }

    /// Always `null` since `anySimpleType` has no properties
    public function getHfpPropValue(string $propName): ?string
    {
        return null;
    }

    /// Always `false` since `anySimpleType` is not numeric
    public function isNumeric(): bool
    {
        return false;
    }
}
