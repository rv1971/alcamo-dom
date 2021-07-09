<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\xsd\Enumerator;

/**
 * @brief Enumeration type definition
 *
 * @date Last reviewed 2021-07-09
 */
class EnumerationType extends AtomicType implements EnumerationTypeInterface
{
    private $enumerators_; ///< Map of enumerator strings to Enumerator objects

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
                $this->enumerators_[$enumerator->value] =
                    new Enumerator($enumerator);
            }
        }

        return $this->enumerators_;
    }
}
