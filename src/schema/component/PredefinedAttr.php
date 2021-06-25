<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class PredefinedAttr extends AbstractPredefinedComponent
{
    private $type_; ///< SimpleType

    public function __construct(
        Schema $schema,
        XName $xName,
        AbstractSimpleType $type
    ) {
        parent::__construct($schema, $xName);

        $this->type_ = $type;
    }

    public function getType(): AbstractSimpleType
    {
        return $this->type_;
    }
}
