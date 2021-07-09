<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\xsd\Enumerator;

/**
 * @brief Definition of a simple type that is a union of enumerations
 *
 * @date Last reviewed 2021-07-09
 */
class EnumerationUnionType extends UnionType implements
    EnumerationTypeInterface
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
            $this->enumerators_ = [];

            foreach ($this->memberTypes_ as $memberType) {
                $this->enumerators_ += $memberType->getEnumerators();
            }
        }

        return $this->enumerators_;
    }
}
