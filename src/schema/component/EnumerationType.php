<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Enumeration type definition
 */
class EnumerationType extends AtomicType implements EnumerationTypeInterface
{
    private $enumerators_; ///< Map of enumerator strings to DOM node objects

    /**
     * @copybrief EnumerationTypeInterface::getEnumerators()
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getEnumerators(): array
    {
        if (!isset($this->enumerators_)) {
            foreach (
                $this->xsdElement_
                    ->query('xsd:restriction/xsd:enumeration') as $enumerator
            ) {
                $this->enumerators_[(string)$enumerator] = $enumerator;
            }
        }

        return $this->enumerators_;
    }
}
