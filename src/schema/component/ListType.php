<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief List type definition
 *
 * @date Last reviewed 2021-07-09
 */
class ListType extends AbstractSimpleType
{
    protected $itemType_; ///< SimpleTypeInterface

    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        ?SimpleTypeInterface $baseType,
        SimpleTypeInterface $itemType
    ) {
        parent::__construct($schema, $xsdElement, $baseType);

        $this->itemType_ = $itemType;
    }

    public function getItemType(): SimpleTypeInterface
    {
        return $this->itemType_;
    }
}
