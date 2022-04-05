<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief Union type definition
 *
 * @date Last reviewed 2021-07-09
 */
class UnionType extends AbstractSimpleType
{
    protected $memberTypes_; ///< Array of SimpleTypeInterface

    /// @param $memberTypes @copybrief getMemberTypes()
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        array $memberTypes
    ) {
        parent::__construct($schema, $xsdElement, null);

        $this->memberTypes_ = $memberTypes;
    }

    /// Array of SimpleTypeInterface
    public function getMemberTypes(): array
    {
        return $this->memberTypes_;
    }
}
