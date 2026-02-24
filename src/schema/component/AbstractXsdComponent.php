<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;
use Psr\Http\Message\UriInterface;

/**
 * @brief XML Schema component defined in an XSD
 *
 * @date Last reviewed 2025-11-06
 */
abstract class AbstractXsdComponent extends AbstractComponent
{
    protected $xsdElement_;     ///< alcamo::dom::decorated::Element

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

    /// @copydoc alcamo::dom::schema::component::ComponentInterface::getXName()
    public function getXName(): ?XName
    {
        return $this->xsdElement_->getComponentXName();
    }

    /**
     * @brief URI that allows to access to definition
     *
     * Can be used to refer to an XSD type as explaind in [Using the id
     * Attribute](https://www.w3.org/TR/swbp-xsch-datatypes/#sec-id-attr).
     */
    public function getUri(): ?UriInterface
    {
        $id = $this->xsdElement_->id ?? $this->xsdElement_->{'xml:id'};

        if (isset($id)) {
            return $this->xsdElement_->ownerDocument->getBaseUri()
                ->withFragment($id);
        } else {
            return null;
        }
    }
}
