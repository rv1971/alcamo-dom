<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Simple type definition
 *
 * @date Last reviewed 2025-11-06
 */
abstract class AbstractSimpleType extends AbstractType implements
    SimpleTypeInterface
{
    private $primitiveType_; ///< self

    /**
     * @brief Factory method creating the most specific type that it can
     * recognize
     *
     * This implies that this methods looks up base/item/member types
     * immediately, unlike other parts of the implementation which delay such
     * lookups until there are explicitly needed.
     */
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        XsdElement $xsdElement
    ): AbstractType {
        $restrictionElement = $xsdElement->query('xsd:restriction')[0];

        if (isset($restrictionElement)) {
            $baseType = isset($restrictionElement->base)
                ? $schema->getGlobalType($restrictionElement->base)
                : self::newFromSchemaAndXsdElement(
                    $schema,
                    $restrictionElement->query('xsd:simpleType')[0]
                );

            if ($baseType instanceof ListType) {
                return new ListType(
                    $schema,
                    $xsdElement,
                    $baseType->getItemType(),
                    $baseType
                );
            }

            if (isset($restrictionElement->query('xsd:enumeration')[0])) {
                return new EnumerationType($schema, $xsdElement, $baseType);
            }

            return new AtomicType($schema, $xsdElement, $baseType);
        }

        $listElement = $xsdElement->query('xsd:list')[0];

        if (isset($listElement)) {
            $itemType = isset($listElement->itemType)
                ? $schema->getGlobalType($listElement->itemType)
                : self::newFromSchemaAndXsdElement(
                    $schema,
                    $listElement->query('xsd:simpleType')[0]
                );

            return new ListType($schema, $xsdElement, $itemType);
        }

        $unionElement = $xsdElement->query('xsd:union')[0];

        if (isset($unionElement)) {
            $memberTypes = [];

            if (isset($unionElement->memberTypes)) {
                foreach ($unionElement->memberTypes as $memberTypeXName) {
                    $memberTypes[(string)$memberTypeXName] =
                        $schema->getGlobalType($memberTypeXName);
                }
            }

            foreach (
                $unionElement->query('xsd:simpleType') as $memberTypeElement
            ) {
                $memberTypes[(string)$memberTypeElement->getComponentXName()] =
                    self::newFromSchemaAndXsdElement(
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

        return new AtomicType($schema, $xsdElement);
    }

    public function getPrimitiveType(): ?SimpleTypeInterface
    {
        if (!isset($this->primitiveType_)) {
            $baseType = $this->getBaseType();

            $this->primitiveType_ = $baseType->getPrimitiveType() ?? $this;
        }

        return $this->primitiveType_;
    }

    public function isEqualToOrDerivedFrom(string $typeXName): bool
    {
        for ($type = $this; isset($type); $type = $type->getBaseType()) {
            if ($type->getXName() == $typeXName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @copydoc alcamo::dom::schema::component::SimpleTypeInterface::getFacet()
     *
     * @warning Only finds facets within the top-level `<xsd:restriction>´
     * element. A facet within
     * `xsd:restriction/xsd:simpleType/xsd:restriction` is not found; such
     * constructs are valid (up to any level of depth), but rarely needed.
     */
    public function getFacet(string $facetName): ?XsdElement
    {
        for (
            $type = $this;
            $type instanceof self;
            $type = $type->getBaseType()
        ) {
            $facet =
                $type->xsdElement_->query("xsd:restriction/xsd:$facetName")[0];

            if (isset($facet)) {
                return $facet;
            }
        }

        return null;
    }
}
