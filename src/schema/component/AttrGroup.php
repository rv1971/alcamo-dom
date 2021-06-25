<?php

namespace alcamo\dom\schema\component;

class AttrGroup extends AbstractXsdComponent
{
    private $attrs_; ///< Map of XName string to SimpleType or PredefinedType

    public function getAttrs(): array
    {
        if (!isset($this->attrs_)) {
            $this->attrs_ = [];

            foreach ($this->xsdElement_ as $element) {
                switch ($element->localName) {
                    case 'attribute':
                        $attr = new Attr($this->schema_, $element);

                        $this->attrs_[(string)$attr->getXName()] = $attr;

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
}
