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
    protected $xsdElement_; ///< alcamo::dom::decorated::Element

    /// XPath to XHTML \<meta> elements in <appinfo>
    protected const XH_META_XPATH =
        'xsd:annotation/xsd:appinfo/xh:meta[@property]';

    /// XPath to XHTML \<link> elements in <appinfo>
    protected const XH_LINK_XPATH = 'xsd:annotation/xsd:appinfo/xh:link[@rel]';

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
        if (isset($this->xsdElement_->id)) {
            return $this->xsdElement_->ownerDocument->getBaseUri()
                ->withFragment($this->xsdElement_->id);
        } else {
            return null;
        }
    }

    /**
     * @brief Get the first `<xh:meta>` element for the given property, if any
     *
     * If the first such element has no `content` attribute, return
     * `null`. This feature might not be particularly useful but is for
     * coherency with
     * alcamo::dom::schema::component::AbstractType::getAppinfoMeta().
     */
    public function getAppinfoMeta(string $property): ?XsdElement
    {
        foreach ($this->xsdElement_->query(static::XH_META_XPATH) as $meta) {
            /* This takes advantage of the magic attribute access in class
               alcamo::dom::extended::Attr which transforms the `property`
               attribute from a CURIE to a URI. A simple comparison within
               the XPath is not sufficient here because XPath 1.0 has no
               means to handle CURIEs. */
            if (in_array($property, $meta->property)) {
                    return isset($meta->content) ? $meta : null;
            }
        }

        return null;
    }

    /// Get the first `<xh:link>` element for the given relation, if any
    public function getAppinfoLink(string $rel): ?XsdElement
    {
        for (
            $type = $this;
            $type instanceof self;
            $type = $type->getBaseType()
        ) {
            foreach (
                $type->xsdElement_->query(static::XH_LINK_XPATH) as $link
            ) {
                /* See getAppinfoMeta(). */
                if (in_array($rel, $link->rel)) {
                    return $link;
                }
            }
        }

        return null;
    }
}
