<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\extended\Element as ExtElement;
use alcamo\dom\xsd\{Document as Xsd, Element as XsdElement};

class Group extends AbstractXsdComponent
{
    public const XSD_NS = Xsd::NS['xsd'];

    private $elements_; ///< Array of Element

    /**
     * @return Array mapping element expanded name string to Element objects
     * for all elements in the content model.

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
                            case 'sequence':
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
