<?php

namespace alcamo\dom\psvi;

use alcamo\dom\HavingDocumentationInterface;
use alcamo\dom\decorated\HavingDocumentationTrait as BaseHavingDocumentationTrait;
use alcamo\dom\schema\component\AbstractXsdComponent;

/**
 * @brief Use XML Schema information for documentation
 */
trait HavingDocumentationTrait
{
    use BaseHavingDocumentationTrait;

    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /** - Use <rdfs:label> or rdfs:label attribute, if applicable. */
        $label = $this->getRdfsLabel($lang, $fallbackFlags);

        if (isset($label)) {
            return $label;
        }

        /*
         * - Otherwise, if the present element has an owl:sameAs attribute and
         * $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_SAME_AS_FRAGMENT, return
         * the fragment part of owl:sameAs.
         */

        if ($fallbackFlags & self::FALLBACK_TO_SAME_AS_FRAGMENT) {
            $label = $this->getSameAsFragment();

            if (isset($label)) {
                return $label;
            }
        }

        /**
         * - Otherwise, if the present element has a type declared in an XSD
         * document and $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_TYPE_NAME, call
         * getLabel() on the type declaration element.
         */
        $type = $this->getType();

        if (
            $type instanceof AbstractXsdComponent
            && $fallbackFlags & self::FALLBACK_TO_TYPE_NAME
        ) {
            return $type->getXsdElement()
                ->getLabel($lang, $fallbackFlags | self::FALLBACK_TO_NAME);
        }

        /**
         * - Otherwise, if $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_NAME, return the present
         * element's local name.  - Otherwise return `null`.
         */
        return $fallbackFlags & self::FALLBACK_TO_NAME
            ? $this->localName
            : null;
    }
}
