<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

/**
 * @brief Attribute declaration predefined in the XML %Schema specification
 *
 * @date Last reviewed 2021-07-09
 */
class PredefinedAttr extends AbstractPredefinedComponent implements
    AttrInterface
{
    private $type_; ///< AbstractSimpleType

    public function __construct(
        Schema $schema,
        XName $xName,
        AbstractSimpleType $type
    ) {
        parent::__construct($schema, $xName);

        $this->type_ = $type;
    }

    public function getType(): SimpleTypeInterface
    {
        return $this->type_;
    }
}
