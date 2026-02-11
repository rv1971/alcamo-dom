<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Type definition
 */
abstract class AbstractType extends AbstractXsdComponent implements
    TypeInterface
{
    /**
     * @brief Factory method creating the most specific type that it can
     * recognize
     */
    public static function newFromSchemaAndXsdElement(
        Schema $schema,
        XsdElement $xsdElement
    ): self {
        return $xsdElement->localName == 'simpleType'
            ? AbstractSimpleType::newFromSchemaAndXsdElement(
                $schema,
                $xsdElement
            )
            : new ComplexType($schema, $xsdElement);
    }

    protected $baseType_; ///< ?TypeInterface

    /**
     * The $baseType parameter has no type declaration because ComplexType
     * initializes it with `false` to mark it as uninitialized.
     */
    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        $baseType = null
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        return $this->baseType_;
    }

    /**
     * @brief Get the first `<xh:meta>` element for the given property in this
     * type or its closest base type, if any
     *
     * If the first such element has no `content` attribute, return
     * `null`. This allows to prevent a type from inheriting a base type's
     * `<xh:meta>`.
     */
    public function getAppinfoMeta(string $property): ?XsdElement
    {
        for (
            $type = $this;
            $type instanceof self;
            $type = $type->getBaseType()
        ) {
            foreach (
                $type->xsdElement_->query(static::XH_META_XPATH) as $meta
            ) {
                /* This takes advantage of the magic attribute access in class
                alcamo::dom::extended::Attr which transforms the `property`
                attribute from a CURIE to a URI. A simple comparison within
                the XPath is not sufficient here because XPath 1.0 has no
                means to handle CURIEs. */
                if (in_array($property, $meta->property)) {
                    return isset($meta->content) ? $meta : null;
                }
            }
        }

        return null;
    }


    /**
     * @brief Get the first `<xh:link>` element for the given relation in this
     * type or its closest base type, if any
     *
     * If the first such element has no `href` attribute, return
     * `null`. This allows to prevent a type from inheriting a base type's
     * `<xh:link>`.
     */
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
                    return isset($link->href) ? $link : null;
                }
            }
        }

        return null;
    }
}
