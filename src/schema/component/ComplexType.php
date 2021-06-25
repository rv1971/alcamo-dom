<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\extended\Element as ExtElement;
use alcamo\dom\schema\Schema;
use alcamo\xml\Xname;

class ComplexType extends AbstractType
{
    public const XSD_NS = Schema::XSD_NS;
    public const XSI_NS = Schema::XSI_NS;
    public const XSI_TYPE_NAME = self::XSI_NS . ' type';

    private $attrs_; ///< Map of XName string to SimpleType or PredefinedType
    private $elements_; ///< Array of Element

    /// Map of element XName string to AbstractType
    private $elementName2Type_;

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
                        ->getGlobalAttr(new XName(self::XSI_NS, 'type'))
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
                    if ($child->namespaceURI == self::XSD_NS) {
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
