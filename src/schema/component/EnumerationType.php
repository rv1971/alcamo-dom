<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\xsd\Enumerator;

/// Defintion of an XSD simple type that is an enumeration.
class EnumerationType extends AtomicType implements EnumerationTypeInterface
{
    private $enumerators_; ///< Map of enumerator strings to Enumerator objects.

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
