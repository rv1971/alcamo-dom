<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element;

/// Defintion of an XSD list simple type.
class ListType extends AbstractSimpleType
{
    protected $itemType_; ///< SimpleTypeInterface

    public function __construct(
        Schema $schema,
        Element $xsdElement,
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
