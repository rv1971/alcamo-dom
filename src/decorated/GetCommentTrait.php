<?php

namespace alcamo\dom\decorated;

use alcamo\dom\GetCommentInterface;

/**
 * @brief Implementation of getComment()
 *
 * This trait is used in a decorator class, not in the Element class because
 * otherwise it would be impossible to override getComment() in a decorator.
 */
trait GetCommentTrait
{
    public function getComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:comment>` child, return its value.
         */
        if (isset($lang)) {
            $commentElement = $this->query("rdfs:comment[@xml:lang = '$lang']")[0];

            if (isset($commentElement)) {
                return $commentElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $commentElement = $this->query("rdfs:comment[not(@xml:lang)]")[0];

            if (isset($commentElement) && $commentElement->getLang() == $lang) {
                return $commentElement->nodeValue;
            }
        }

        /**
         * - Otherwise (i.e. if no specific language is requested or the
         * requested language has not been found), if the present element (not
         * a descendent of it) has an `rdfs:comment` attribute, return its
         * content. This way, the attribute, if present, acts as a
         * language-agnostic default comment.
         */
        $commentAttr = $this->{'rdfs:comment'};

        if (isset($commentAttr)) {
            return $commentAttr;
        }

        /*
         * - Otherwise, if no specific language is requested or $fallbackFlags
         * contains GetCommentInterface::FALLBACK_TO_OTHER_LANG, return the
         * first `\<rdfs:comment>` child found, regardless of its
         * language. Thus, the document author decides about the default
         * fallback language by putting the corresponding comment in the first
         * place.
         */
        if (!isset($lang) || $fallbackFlags & self::FALLBACK_TO_OTHER_LANG) {
            $commentElement = $this->query("rdfs:comment")[0];

            if (isset($commentElement)) {
                return $commentElement->nodeValue;
            }
        }

        /**
         * - Otherwise return `null`.
         */
        return null;
    }
}
