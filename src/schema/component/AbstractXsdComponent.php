<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;
use Psr\Http\Message\UriInterface;
use alcamo\xml\XName;

/**
 * @brief XML %Schema component defined in an XSD
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

    /**
     * @brief URL that allows to access to definition
     *
     * Can be used to refer to an XSD type as explaind in [Using the id
     * Attribute](https://www.w3.org/TR/swbp-xsch-datatypes/#sec-id-attr).
     */
    public function getUri(): ?UriInterface
    {
        if (isset($this->xsdElement_->id)) {
            return $this->xsdElement_->ownerDocument->getBaseUri()
                ->withFragment($this->xsdElement_->id);
        } else {
            return null;
        }
    }

    /**
     * Get the first `xsd:annotation/xsd:appinfo/xh:meta` element for the
     * given property in the closest ancestor-or-self type, if any.
     */
    public function getAppinfoMeta(string $property): ?XsdElement
    {
        for (
            $type = $this;
            $type instanceof AbstractXsdComponent;
            $type = $type->getBaseType()
        ) {
            foreach (
                $type->getXsdElement()->query(
                    "xsd:annotation/xsd:appinfo/xh:meta[@property]"
                ) as $meta
            ) {
                if ($meta->property == $property) {
                    return $meta;
                }
            }
        }

        return null;
    }

    /**
     * Get the first `xsd:annotation/xsd:appinfo/xh:link` element for the
     * given property in the closest ancestor-or-self type, if any.
     */
    public function getAppinfoLink(string $rel): ?XsdElement
    {
        for (
            $type = $this;
            $type instanceof AbstractXsdComponent;
            $type = $type->getBaseType()
        ) {
            foreach (
                $type->getXsdElement()->query(
                    "xsd:annotation/xsd:appinfo/xh:link[@rel]"
                ) as $link
            ) {
                if ($link->rel == $rel) {
                    return $link;
                }
            }
        }

        return null;
    }
}
