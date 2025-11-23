<?php

namespace alcamo\dom\schema\component;

/**
 * @brief Enumeration type definition
 *
 * @date Last reviewed 2025-11-06
 */
class EnumerationType extends AtomicType implements EnumerationTypeInterface
{
    private const ENUMERATION_XPATH = 'xsd:restriction/xsd:enumeration';

    private $enumerators_; ///< Map of enumerator strings to DOMElement objects

    /**
     * @copybrief alcamo::dom::schema::component::EnumerationTypeInterface::getEnumerators()
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getEnumerators(): array
    {
        if (!isset($this->enumerators_)) {
            $this->enumerators_ = [];

            foreach (
                $this->xsdElement_
                    ->query(self::ENUMERATION_XPATH) as $enumerator
            ) {
                $this->enumerators_[(string)$enumerator] = $enumerator;
            }
        }

        return $this->enumerators_;
    }
}
