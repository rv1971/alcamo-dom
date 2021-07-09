<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @brief Type definition predefined in the XML Schema specification
 *
 * @date Last reviewed 2021-07-09
 */
class PredefinedType extends AbstractPredefinedComponent implements
    TypeInterface
{
    private $baseType_; ///< ?PredefinedType

    public function __construct(
        Schema $schema,
        XName $xName,
        ?TypeInterface $baseType = null
    ) {
        parent::__construct($schema, $xName);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        return $this->baseType_;
    }
}
