<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetLabelInterface;
use alcamo\dom\schema\component\AbstractXsdComponent;

/**
 * @brief Implementation of getLabel()
 *
 * This trait is not used in the Element class because this would make it
 * impossible to override getLabel() in a decorator.
 */
trait GetLabelTrait
{
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:label>` child, return its value.
         */
        if (isset($lang)) {
            $labelElement = $this->query("rdfs:label[@xml:lang = '$lang']")[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $labelElement = $this->query("rdfs:label[not(@xml:lang)]")[0];

            if (isset($labelElement) && $labelElement->getLang() == $lang) {
                return $labelElement->nodeValue;
            }
        }

        /**
         * - Otherwise (i.e. if no specific language is requested or the
         * requested language has not been found), if the present element (not
         * a descendent of it) has an `rdfs:label` attribute, return its
         * content. This way, the attribute, if present, acts as a
         * language-agnostic default label.
         */
        $labelAttr = $this->{'rdfs:label'};

        if (isset($labelAttr)) {
            return $labelAttr;
        }

        /*
         * - Otherwise, if no specific language is requested or $fallbackFlags
         * contains GetLabelInterface::FALLBACK_TO_OTHER_LANG, return the
         * first `\<rdfs:label>` child found, regardless of its
         * language. Thus, the document author decides about the default
         * fallback language by putting the corresponding label in the first
         * place.
         */
        if (!isset($lang) || $fallbackFlags & self::FALLBACK_TO_OTHER_LANG) {
            $labelElement = $this->query("rdfs:label")[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
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
