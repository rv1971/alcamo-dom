<?php

namespace alcamo\dom\psvi;

use alcamo\dom\HavingDocumentationInterface;
use alcamo\dom\decorated\HavingDocumentationTrait as BaseHavingDocumentationTrait;
use alcamo\dom\schema\component\AbstractXsdComponent;

/**
 * @brief Use XML Schema information for documentation
 *
 * @date Last reviewed 2025-11-25
 */
trait HavingDocumentationTrait
{
    use BaseHavingDocumentationTrait;

    /** @copydoc alcamo::dom::HavingDocumentationInterface::getLabel() */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /** Proceed as in
         *  alcamo::dom::decorated::HavingDocumentationTrait::getLabel(), but
         *  without fallback to element's local name. If a label is found,
         *  return it. */
        $label =
            parent::getLabel($lang, $fallbackFlags & ~self::FALLBACK_TO_NAME);

        if (isset($label)) {
            return $label;
        }

        /**
         * - Otherwise, if the present element has a type declared in an XSD
         * document and $fallbackFlags contains
         * alcamo::dom::HavingDocumentationInterface::FALLBACK_TO_TYPE_NAME,
         * call getLabel() on the type declaration element.
         */
        $type = $this->handler_->getType();

        if (
            $type instanceof AbstractXsdComponent
            && $fallbackFlags & self::FALLBACK_TO_TYPE_NAME
        ) {
            return $type->getRdfaData()['rdfs:label']->findLang(
                $lang,
                !($fallbackFlags & self::FALLBACK_TO_OTHER_LANG)
            );
        }

        /**
         * - Otherwise, if $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_NAME, return the present
         * element's local name.  - Otherwise return `null`.
         */
        return $fallbackFlags & self::FALLBACK_TO_NAME
            ? $this->handler_->localName
            : null;
    }
}
