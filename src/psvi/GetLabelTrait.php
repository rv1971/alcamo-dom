<?php

namespace alcamo\dom\psvi;

use alcamo\dom\GetLabelInterface;
use alcamo\dom\decorated\GetLabelTrait as BaseGetLabelTrait;
use alcamo\dom\schema\component\AbstractXsdComponent;

/**
 * @brief Implementation of getLabel()
 *
 * This trait is used in a decorator class, not in the Element class because
 * otherwise it would be impossible to override getLabel() in a decorator.
 */
trait GetLabelTrait
{
    use BaseGetLabelTrait;

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
         * GetLabelInterface::FALLBACK_TO_SAME_AS_FRAGMENT, return the
         * fragment part of owl:sameAs.
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
         * GetLabelInterface::FALLBACK_TO_TYPE_NAME, call getLabel() on the
         * type declaration element.
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
         * GetLabelInterface::FALLBACK_TO_NAME, return the present element's
         * local name.
         * - Otherwise return `null`.
         */
        return $fallbackFlags & self::FALLBACK_TO_NAME
            ? $this->localName
            : null;
    }
}
