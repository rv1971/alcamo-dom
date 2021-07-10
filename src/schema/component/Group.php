<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\xsd\Document as Xsd;

/**
 * @brief Model group definition
 *
 * @date Last reviewed 2021-07-10
 */
class Group extends AbstractXsdComponent
{
    private $elements_; ///< Array of Element

    /**
     * @brief Map of element XName string to Element for all elements in the
     * content model
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
            $stack = [ $this->xsdElement_ ];

            $this->elements_ = [];

            while ($stack) {
                foreach (array_pop($stack) as $child) {
                    if ($child->namespaceURI == Xsd::XSD_NS) {
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
