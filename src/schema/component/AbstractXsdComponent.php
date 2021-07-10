<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element as XsdElement;
use alcamo\xml\XName;

/**
 * @brief XML %Schema component defined in an XSD
 *
 * @date Last reviewed 2021-07-09
 */
abstract class AbstractXsdComponent extends AbstractComponent
{
    protected $xsdElement_; ///< alcamo::dom::xsd::Element

    public function __construct(Schema $schema, XsdElement $xsdElement)
    {
        parent::__construct($schema);

        $this->xsdElement_ = $xsdElement;
    }

    /// XSD element that defines the component
    public function getXsdElement(): XsdElement
    {
        return $this->xsdElement_;
    }

    /// @copydoc ComponentInterface::getXName()
    public function getXName(): ?XName
    {
        return $this->xsdElement_->getComponentXName();
    }
}
