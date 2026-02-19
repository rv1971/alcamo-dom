<?php

namespace alcamo\dom\decorated;

use alcamo\dom\HavingDocumentationInterface;

/**
 * @brief Implementation of HavingDocumentationInterface
 *
 * This trait is used in a decorator class, not in the Element class because
 * otherwise it would be impossible to override its methods in a decorator.
 *
 * @date Last reviewed 2025-11-05
 */
trait HavingDocumentationTrait
{
    use HavingRdfaDataTrait;

    /** @copydoc alcamo::dom::HavingDocumentationInterface::getLabel() */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /** - Use rdfs:label from metadata in XML, if present. */
        $label = $this->getRdfaData()->findStmtWithLang(
            'rdfs:label',
            $lang,
            !($fallbackFlags & self::FALLBACK_TO_OTHER_LANG)
        );

        if (isset($label)) {
            return $label;
        }

        /*
         * - Otherwise, if the present element has an owl:sameAs attribute and
         * $fallbackFlags contains
         * alcamo::dom::HavingDocumentationInterface::FALLBACK_TO_SAME_AS_FRAGMENT,
         * return the fragment part of owl:sameAs.
         */

        if ($fallbackFlags & self::FALLBACK_TO_SAME_AS_FRAGMENT) {
            $label = $this->getSameAsFragment();

            if (isset($label)) {
                return $label;
            }
        }

        /**
         * - Otherwise, if $fallbackFlags contains
         * alcamo::dom::HavingDocumentationInterface::FALLBACK_TO_NAME, return
         * the present element's local name.  - Otherwise return `null`.
         */
        return $fallbackFlags & self::FALLBACK_TO_NAME
            ? $this->handler_->localName
            : null;
    }

    /// Get fragment part of URI in owl:sameAs attribute, if any
    protected function getSameAsFragment(): ?string
    {
        $sameAs = $this->getRdfaData()->getFirstValueOrUri('owl:sameAs');

        return isset($sameAs) ? $sameAs->getFragment() : null;
    }
}
