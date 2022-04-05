<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\dom\decorated\Element as XsdElement;

/**
 * @brief Element declaration
 *
 * @date Last reviewed 2021-07-10
 */
class Element extends AbstractXsdComponent
{
    private $refElement_; ///< ?Element
    private $type_;       ///< TypeInterface

    public function __construct(Schema $schema, XsdElement $xsdElement)
    {
        parent::__construct($schema, $xsdElement);

        if (isset($this->xsdElement_->ref)) {
            $this->refElement_ =
                $this->schema_->getGlobalElement($this->xsdElement_->ref);
        }
    }

    /// Element indicated by the `ref` attribute, if any
    public function getRefElement(): ?self
    {
        return $this->refElement_;
    }

    /**
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getType(): TypeInterface
    {
        if (!isset($this->type_)) {
            switch (true) {
                case isset($this->refElement_):
                    $this->type_ = $this->refElement_->getType();
                    break;

                case isset($this->xsdElement_->type):
                    $this->type_ =
                        $this->schema_->getGlobalType($this->xsdElement_->type);
                    break;

                case ($complexTypeElement =
                      $this->xsdElement_->query('xsd:complexType')[0]):
                    $this->type_ =
                        new ComplexType($this->schema_, $complexTypeElement);
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
                    $this->type_ = $this->schema_->getAnyType();
            }
        }

        return $this->type_;
    }
}
