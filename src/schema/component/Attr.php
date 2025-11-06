<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Attribute declaration
 *
 * @date Last reviewed 2025-11-06
 */
class Attr extends AbstractXsdComponent implements AttrInterface
{
    private $refAttr_; ///< ?Attr
    private $type_;    ///< SimpleTypeInterface

    public function __construct(Schema $schema, XsdElement $xsdElement)
    {
        parent::__construct($schema, $xsdElement);

        if (isset($this->xsdElement_->ref)) {
            $this->refAttr_ =
                $this->schema_->getGlobalAttr($this->xsdElement_->ref);
        }
    }

    /// Attribute indicated by the `ref` attribute, if any
    public function getRefAttr(): ?self
    {
        return $this->refAttr_;
    }

    /**
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getType(): SimpleTypeInterface
    {
        if (!isset($this->type_)) {
            switch (true) {
                case isset($this->refAttr_):
                    $this->type_ = $this->refAttr_->getType();
                    break;

                case isset($this->xsdElement_->type):
                    $this->type_ = $this->schema_
                        ->getGlobalType($this->xsdElement_->type)
                        ?? $this->schema_->getAnySimpleType();
                    break;

                case ($typeElement =
                      $this->xsdElement_->query('xsd:simpleType')[0]):
                    $this->type_ =
                        AbstractSimpleType::newFromSchemaAndXsdElement(
                            $this->schema_,
                            $typeElement
                        );
                    break;

                default:
                    $this->type_ = $this->schema_->getAnySimpleType();
            }
        }

        return $this->type_;
    }
}
