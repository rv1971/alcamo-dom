<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief Simple type definition
 *
 * @date Last reviewed 2021-07-10
 */
abstract class AbstractSimpleType extends AbstractXsdComponent implements
    SimpleTypeInterface
{
    private $baseType_; ///< ?AbstractType

    /// Factory method creating the most specific type that it can recognize
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        XsdElement $xsdElement
    ): self {
        $xPath = $xsdElement->ownerDocument->getXPath();

        $restrictionElement = $xPath->query('xsd:restriction', $xsdElement)[0];

        if (isset($restrictionElement)) {
            $baseType = isset($restrictionElement->base)
                ? $schema->getGlobalType($restrictionElement->base)
                : self::newFromSchemaAndXsdElement(
                    $schema,
                    $xPath->query('xsd:simpleType', $restrictionElement)[0]
                );

            if ($baseType instanceof ListType) {
                return new ListType(
                    $schema,
                    $xsdElement,
                    $baseType,
                    $baseType->getItemType()
                );
            }

            if ($xPath->query('xsd:enumeration', $restrictionElement)[0]) {
                return new EnumerationType($schema, $xsdElement, $baseType);
            }

            return new AtomicType($schema, $xsdElement, $baseType);
        }

        $listElement = $xPath->query('xsd:list', $xsdElement)[0];

        if (isset($listElement)) {
            if (isset($listElement->itemType)) {
                $itemType =
                    $schema->getGlobalType($listElement->itemType);
            } else {
                $itemType = self::newFromSchemaAndXsdElement(
                    $schema,
                    $xPath->query('xsd:simpleType', $listElement)[0]
                );
            }

            return new ListType($schema, $xsdElement, null, $itemType);
        }

        $unionElement = $xPath->query('xsd:union', $xsdElement)[0];

        if (isset($unionElement)) {
            $memberTypes = [];

            if (isset($unionElement->memberTypes)) {
                foreach ($unionElement->memberTypes as $memberTypeXName) {
                    $memberTypes[] =
                        $schema->getGlobalType($memberTypeXName);
                }
            }

            foreach (
                $xPath->query(
                    'xsd:simpleType',
                    $unionElement
                ) as $memberTypeElement
            ) {
                $memberTypes[] = self::newFromSchemaAndXsdElement(
                    $schema,
                    $memberTypeElement
                );
            }

            $isEnumeration = true;

            foreach ($memberTypes as $memberType) {
                if (!($memberType instanceof EnumerationTypeInterface)) {
                    $isEnumeration = false;
                    break;
                }
            }

            return $isEnumeration
                ? new EnumerationUnionType($schema, $xsdElement, $memberTypes)
                : new UnionType($schema, $xsdElement, $memberTypes);
        }

        return new AtomicType($schema, $xsdElement, null);
    }

    protected function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        ?TypeInterface $baseType
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        return $this->baseType_;
    }

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

    /**
     * @warning Only finds facets within the top-level `xsd:restriction´
     * element. A facet within
     * `xsd:restriction/xsd:simpleType/xsd:restriction´ is not found; such
     * constructs are valid (up to any level of depth), but rarely needed.
     */
    public function getFacetValue(string $facetName)
    {
        for (
            $type = $this;
            $type instanceof AbstractXsdComponent;
            $type = $type->getBaseType()
        ) {
            $facetValue = $type->getXsdElement()
                ->query("xsd:restriction/xsd:$facetName/@value")[0];

            if (isset($facetValue)) {
                return (string)$facetValue;
            }
        }

        return null;
    }
}
