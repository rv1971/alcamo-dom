<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element as XsdElement;

/**
 * @brief Attribute declaration in an XSD
 *
 * @date Last reviewed 2021-07-09
 */
class Attr extends AbstractXsdComponent
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

    /// Attr indicated by the `ref` attribute, if any
    public function getRefAttr(): ?self
    {
        return $this->refAttr_;
    }

    public function getType(): SimpleTypeInterface
    {
        if (!isset($this->type_)) {
            switch (true) {
                case isset($this->refAttr_):
                    $this->type_ = $this->refAttr_->getType();
                    break;

                case isset($this->xsdElement_->type):
                    $this->type_ = $this->schema_
                        ->getGlobalType($this->xsdElement_->type);
                    break;

                case ($simpleTypeElement =
                      $this->xsdElement_->query('xsd:simpleType')[0]):
                    $this->type_ =
                        AbstractSimpleType::newFromSchemaAndXsdElement(
                            $this->schema_,
                            $simpleTypeElement
                        );
                    break;

                default:
                    $this->type_ = $this->schema_->getAnySimpleType();
            }
        }

        return $this->type_;
    }
}
