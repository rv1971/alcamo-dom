<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\Schema;

/**
 * @brief Complex type definition
 *
 * @date Last reviewed 2025-11-06
 */
class ComplexType extends AbstractType implements TypeInterface
{
    private const DERIVATION_XPATH =
        'xsd:simpleContent/xsd:restriction'
        . '|xsd:simpleContent/xsd:extension'
        . '|xsd:complexContent/xsd:restriction'
        . '|xsd:complexContent/xsd:extension';

    private const XSI_TYPE_NAME = self::XSI_NS . ' type';

    private $derivation_; ///< ?Element

    private $attrs_;      ///< Map of XName string to SimpleTypeInterface
    private $elements_;   ///< Map of element XName string to Element

    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        $baseType = null
    ) {
        parent::__construct($schema, $xsdElement, $baseType ?? false);

        $this->derivation_ =
            $this->xsdElement_->query(self::DERIVATION_XPATH)[0];
    }

    /**
     * @copybrief alcamo::dom::schema::component::TypeInterface::getBaseType()
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getBaseType(): ?TypeInterface
    {
        if ($this->baseType_ === false) {
            $baseXName =
                isset($this->derivation_) ? $this->derivation_->base : null;

            $this->baseType_ = isset($baseXName)
                ? $this->schema_->getGlobalType($baseXName)
                : null;
        }

        return $this->baseType_;
    }

    /**
     * @brief Get map of XName string to Attr
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getAttrs(): array
    {
        if (!isset($this->attrs_)) {
            if ($this->getBaseType() instanceof self) {
                $this->attrs_ = $this->getBaseType()->getAttrs();

                $attrContainer = $this->derivation_;
            } else {
                /* Predefine xsi:type if not inheriting it from base type. */
                $this->attrs_ = [
                    self::XSI_TYPE_NAME
                    => $this->schema_->getGlobalAttr(self::XSI_TYPE_NAME)
                ];

                $attrContainer = $this->xsdElement_;
            }

            foreach ($attrContainer as $attrElement) {
                switch ($attrElement->localName) {
                    case 'attribute':
                        /* Remove prohibited attributes. */
                        if ($attrElement->use == 'prohibited') {
                            unset($this->attrs_[
                                (string)$attrElement->getComponentXName()
                            ]);
                        } else {
                            $attr = new Attr($this->schema_, $attrElement);

                            $this->attrs_[(string)$attr->getXName()] = $attr;
                        }

                        break;

                    case 'attributeGroup':
                        $this->attrs_ += $this->schema_
                            ->getGlobalAttrGroup($attrElement->ref)->getAttrs();
                        break;
                }
            }
        }

        return $this->attrs_;
    }

    /**
     * Map of element XName string to Element for all elements in the content
     * model
     *
     * @warning Content models containing two elements with the same expanded
     * name but different types are not supported.
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getElements(): array
    {
        if (!isset($this->elements_)) {
            $this->elements_ = [];

            $stack = [ $this->xsdElement_ ];

            while ($stack) {
                foreach (array_pop($stack) as $element) {
                    switch ($element->localName) {
                        case 'element':
                            $element = new Element($this->schema_, $element);

                            $this->elements_[(string)$element->getXName()] =
                                $element;

                            break;

                        case 'choice':
                        case 'complexContent':
                        case 'sequence':
                            $stack[] = $element;
                            break;

                        case 'extension':
                        case 'restriction':
                            if (isset($element->base)) {
                                $baseType = $this->schema_
                                    ->getGlobalType($element->base);

                                if ($baseType instanceof self) {
                                    $this->elements_ +=
                                        $baseType->getElements();
                                }
                            }

                            $stack[] = $element;

                            break;

                        case 'group':
                            $this->elements_ += $this->schema_
                                ->getGlobalGroup($element->ref)
                                ->getElements();
                            break;
                    }
                }
            }
        }

        return $this->elements_;
    }
}
