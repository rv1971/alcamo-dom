<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element;
use alcamo\xml\XName;

abstract class AbstractXsdComponent extends AbstractComponent
{
    protected $xsdElement_; ///< Element.

    public function __construct(Schema $schema, Element $xsdElement)
    {
        parent::__construct($schema);

        $this->xsdElement_ = $xsdElement;
    }

    public function getXsdElement(): Element
    {
        return $this->xsdElement_;
    }

    public function getXName(): ?XName
    {
        return $this->xsdElement_->getComponentXName();
    }
}
