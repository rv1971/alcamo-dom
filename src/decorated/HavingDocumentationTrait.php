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
    /** @copydoc alcamo::dom::HavingDocumentationInterface::getLabel() */
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
         * - Otherwise, if $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_NAME, return the present
         * element's local name.
         * - Otherwise return `null`.
         */
        return $fallbackFlags & self::FALLBACK_TO_NAME
            ? $this->localName
            : null;
    }

    /** @copydoc alcamo::dom::HavingDocumentationInterface::getComment() */
    public function getComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        return $this->getRdfsComment($lang, $fallbackFlags);
    }

    /// Get label from <rdfs:label> or rdfs:label attribute, if any
    protected function getRdfsLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:label>` child for it, return its value.
         */
        if (isset($lang)) {
            $labelElement = $this->query(
                static::RDFS_LABEL_XPATH . "[@xml:lang = '$lang']"
            )[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $labelElement =
                $this->query(static::RDFS_LABEL_XPATH . "[not(@xml:lang)]")[0];

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
         * contains HavingDocumentationInterface::FALLBACK_TO_OTHER_LANG,
         * return the first `\<rdfs:label>` child found, regardless of its
         * language. Thus, the document author decides about the default
         * fallback language by putting the corresponding label in the first
         * place.
         */
        if (!isset($lang) || $fallbackFlags & self::FALLBACK_TO_OTHER_LANG) {
            $labelElement = $this->query(static::RDFS_LABEL_XPATH)[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
            }
        }

        /** - Otherwise, return `null`. */
        return null;
    }

    /// Get fragment of owl:sameAs attribute, if any
    protected function getSameAsFragment(): ?string
    {
        $sameAs = $this->{'owl:sameAs'};

        return isset($sameAs) ? $sameAs->getFragment() : null;
    }

    /// Get comment from <rdfs:comment> or rdfs:comment attribute, if any
    protected function getRdfsComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:comment>` child for it, return its value.
         */
        if (isset($lang)) {
            $commentElement = $this->query(
                static::RDFS_COMMENT_XPATH . "[@xml:lang = '$lang']"
            )[0];

            if (isset($commentElement)) {
                return $commentElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $commentElement =
                $this->query(static::RDFS_COMMENT_XPATH . "[not(@xml:lang)]")[0];

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
            $commentElement = $this->query(static::RDFS_COMMENT_XPATH)[0];

            if (isset($commentElement)) {
                return $commentElement->nodeValue;
            }
        }

        return null;
    }
}
