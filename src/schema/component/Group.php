<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Model group definition
 *
 * @date Last reviewed 2025-11-06
 */
class Group extends AbstractXsdComponent
{
    private $elements_; ///< XName string to Element

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
                foreach (array_pop($stack) as $element) {
                    switch ($element->localName) {
                        case 'element':
                            $element = new Element($this->schema_, $element);

                            $this->elements_[(string)$element->getXName()] =
                                $element;

                            break;

                        case 'choice':
                        case 'sequence':
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
