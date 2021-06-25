<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element;

abstract class AbstractSimpleType extends AbstractType implements SimpleTypeInterface
{
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        Element $xsdElement
    ): self {
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
                    $baseType,
                    $baseType->getItemType()
                );
            }

            if ($restrictionElement->query('xsd:enumeration')[0]) {
                return new EnumerationType($schema, $xsdElement, $baseType);
            }

            return new AtomicType($schema, $xsdElement, $baseType);
        }

        $listElement = $xsdElement->query('xsd:list')[0];

        if (isset($listElement)) {
            if (isset($listElement->itemType)) {
                $itemType =
                    $schema->getGlobalType($listElement->itemType);
            } else {
                $itemType = self::newFromSchemaAndXsdElement(
                    $schema,
                    $listElement->query('xsd:simpleType')[0]
                );
            }

            return new ListType($schema, $xsdElement, null, $itemType);
        }

        $unionElement = $xsdElement->query('xsd:union')[0];

        if (isset($unionElement)) {
            $memberTypes = [];

            if (isset($unionElement->memberTypes)) {
                foreach ($unionElement->memberTypes as $memberTypeXName) {
                    $memberTypes[] =
                        $schema->getGlobalType($memberTypeXName);
                }
            }

            foreach (
                $unionElement->query('xsd:simpleType') as $memberTypeElement
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

    /** Unlike AbstractType::__construct(), the third parameter must be
     *  provided, and AbstractSimpleType objects can only be created via
     *  newFromSchemaAndXsdElement() or from derived classes. */
    protected function __construct(
        Schema $schema,
        Element $xsdElement,
        ?TypeInterface $baseType
    ) {
        parent::__construct($schema, $xsdElement, $baseType);
    }
}
