<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\xsd\Enumerator;

/// Defintion of an XSD simple type that is a union of enumerations.
class EnumerationUnionType extends UnionType implements
    EnumerationTypeInterface
{
    private $enumerators_; ///< Map of enumerator strings to Enumerator objects.

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
