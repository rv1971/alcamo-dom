<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

abstract class AbstractPredefinedComponent extends AbstractComponent
{
    private $xName_; ///< XName

    public function __construct(Schema $schema, XName $xName)
    {
        parent::__construct($schema);
        $this->xName_ = $xName;
    }

    public function getXName(): XName
    {
        return $this->xName_;
    }
}
