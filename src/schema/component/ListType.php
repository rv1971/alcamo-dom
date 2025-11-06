<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as Element;

/**
 * @brief List type definition
 *
 * @date Last reviewed 2025-11-06
 */
class ListType extends AbstractSimpleType
{
    protected $itemType_; ///< SimpleTypeInterface

    public function __construct(
        Schema $schema,
        Element $xsdElement,
        SimpleTypeInterface $itemType,
        ?SimpleTypeInterface $baseType = null
    ) {
        parent::__construct($schema, $xsdElement, $baseType);

        $this->itemType_ = $itemType;
    }

    public function getItemType(): SimpleTypeInterface
    {
        return $this->itemType_;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::getHfpPropValue()
     *
     * @return Always `null` since list types have no properties.
     */
    public function getHfpPropValue(string $propName): ?string
    {
        return null;
    }

    /**
     * @copydoc
     * alcamo::dom::schema::component::SimpleTypeInterface::isNumeric()
     *
     * @return Always `false` since list types are not numeric.
     */
    public function isNumeric(): bool
    {
        return false;
    }
}
