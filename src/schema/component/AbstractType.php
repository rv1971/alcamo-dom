<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Type definition
 */
abstract class AbstractType extends AbstractXsdComponent implements
    TypeInterface
{
    /**
     * @brief Factory method creating the most specific type that it can
     * recognize
     */
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        XsdElement $xsdElement
    ): self {
        return $xsdElement->localName == 'simpleType'
            ? AbstractSimpleType::newFromSchemaAndXsdElement(
                $schema,
                $xsdElement
            )
            : new ComplexType($schema, $xsdElement);
    }

    protected $baseType_; ///< ?TypeInterface

    /**
     * The $baseType parameter has no type declaration because ComplexType
     * initializes it with `false` to mark it as uninitialized.
     */
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        $baseType = null
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        return $this->baseType_;
    }
}
