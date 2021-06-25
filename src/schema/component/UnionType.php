<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element;
use alcamo\xml\XName;

/// Defintion of an XSD union simple type.
class UnionType extends AbstractSimpleType
{
    protected $memberTypes_; ///< Array of AbstractSimpleType.

    public function __construct(
        Schema $schema,
        Element $xsdElement,
        array $memberTypes
    ) {
        parent::__construct($schema, $xsdElement, null);

        $this->memberTypes_ = $memberTypes;
    }

    public function getMemberTypes(): array
    {
        return $this->memberTypes_;
    }
}
