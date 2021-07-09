<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\Document;
use alcamo\dom\extended\Element as ExtElement;
use alcamo\dom\schema\Schema;
use alcamo\dom\xsd\Element as XsdElement;
use alcamo\xml\Xname;

class ComplexType extends AbstractXsdComponent implements TypeInterface
{
    public const XSI_TYPE_NAME = Document::XSI_NS . ' type';

    private $baseType_; ///< ?AbstractType
    private $attrs_; ///< Map of XName string to SimpleType or PredefinedType
    private $elements_; ///< Array of Element

    /// Map of element XName string to AbstractType
    private $elementName2Type_;

    public function __construct(
        Schema $schema,
        XsdElement $xsdElement,
        $baseType = false
    ) {
        parent::__construct($schema, $xsdElement);

        $this->baseType_ = $baseType;
    }

    public function getBaseType(): ?TypeInterface
    {
        if ($this->baseType_ === false) {
            $baseXNameElement =
                $this->xsdElement_->query('xsd:*/xsd:*[@base]')[0];

            $this->baseType_ = isset($baseXNameElement)
                ? $this->schema_->getGlobalType($baseXNameElement->base)
                : null;
        }

        return $this->baseType_;
    }

    public function getAttrs(): array
    {
        if (!isset($this->attrs_)) {
            if ($this->getBaseType() instanceof self) {
                $this->attrs_ = $this->getBaseType()->getAttrs();
            } else {
                // predefine xsi:type if not inheriting it from base type
                $this->attrs_ = [
                    self::XSI_TYPE_NAME
                    => $this->schema_
                        ->getGlobalAttr(new XName(Document::XSI_NS, 'type'))
                ];
            }

            $extensionOrRestriction =
                $this->xsdElement_->query(
                    'xsd:complexContent/xsd:restriction'
                    . '|xsd:complexContent/xsd:extension'
                    . '|xsd:simpleContent/xsd:restriction'
                    . '|xsd:simpleContent/xsd:extension'
                )[0];

            $attrParent = $extensionOrRestriction ?? $this->xsdElement_;

            foreach ($attrParent as $element) {
                switch ($element->localName) {
                    case 'attribute':
                        if ($element->use == 'prohibited') {
                            unset($this->attrs_[
                                (string)$element->getComponentXName()
                            ]);
                        } else {
                            $attr = new Attr($this->schema_, $element);

                            $this->attrs_[(string)$attr->getXName()] = $attr;
                        }

                        break;

                    case 'attributeGroup':
                        $this->attrs_ += $this->schema_
                            ->getGlobalAttrGroup($element->ref)->getAttrs();
                        break;
                }
            }
        }

        return $this->attrs_;
    }

    /**
     * @return array mapping element expanded name string to Element objects
     * for all elements in the content model
     *
     * @warning Content models containing two elements with the same expanded
     * name but different types are not supported.
     */
    public function getElements(): array
    {
        if (!isset($this->elements_)) {
            $stack = [ $this->xsdElement_ ];

            $this->elements_ = [];

            while ($stack) {
                foreach (array_pop($stack) as $child) {
                    if ($child->namespaceURI == Document::XSD_NS) {
                        switch ($child->localName) {
                            case 'element':
                                $element = new Element($this->schema_, $child);

                                $this->elements_[(string)$element->getXName()] =
                                    $element;

                                break;

                            case 'choice':
                            case 'complexContent':
                            case 'sequence':
                                $stack[] = $child;
                                break;

                            case 'extension':
                            case 'restriction':
                                if (isset($child->base)) {
                                    $baseType = $this->schema_
                                        ->getGlobalType($child->base);

                                    if ($baseType instanceof self) {
                                        $this->elements_ +=
                                            $baseType->getElements();
                                    }
                                }

                                $stack[] = $child;

                                break;

                            case 'group':
                                $this->elements_ += $this->schema_
                                    ->getGlobalGroup($child->ref)
                                    ->getElements();
                                break;
                        }
                    }
                }
            }
        }

        return $this->elements_;
    }
}
