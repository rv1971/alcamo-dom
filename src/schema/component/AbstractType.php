<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element;

class AbstractType extends AbstractXsdComponent implements TypeInterface
{
    private $baseType_; ///< ?AbstractType

    public function __construct(
        Schema $schema,
        Element $xsdElement,
        $baseType = false
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        if ($this->baseType_ === false) {
            /** This branch is executed for complex types only since
             *  AbstractSimpleType::__construct() always initializes the base
             *  type. */
            $baseXNameElement =
                $this->xsdElement_->query('xsd:*/xsd:*[@base]')[0];

            $this->baseType_ = isset($baseXNameElement)
                ? $this->schema_->getGlobalType($baseXNameElement->base)
                : null;
        }

        return $this->baseType_;
    }
}
