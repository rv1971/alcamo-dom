<?php

namespace alcamo\dom\xsd;

use alcamo\dom\{GetCommentInterface, GetLabelInterface};
use alcamo\dom\decorated\AbstractDecorator;
use alcamo\xml\XName;

/// Decorator providing getLabel()
class Decorator extends AbstractDecorator implements
    GetCommentInterface,
    GetLabelInterface
{
    private $xComponentName_ = false; ///< ?XName

    /**
     * @brief Get the component's extended name, if possible
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getComponentXName(): ?XName
    {
        if ($this->xComponentName_ === false) {
            /* The implicit call to offsetGet() includes a call to
             * RegisteredNodeTrait::register(). */

            if (isset($this->ref)) {
                /** - If there is a `ref` attribute, return its value. */
                $this->xComponentName_ = $this->ref;
            } elseif (isset($this->name)) {
                /** - If there is a `name` attribute, build an extended name
                 * from it. */
                $documentElement = $this->ownerDocument->documentElement;

                /* The component name has the target namespace as
                 * its namespace iff it is global, or if it is an attribute
                 * declaration and the form of local attributes is
                 * qualified. */
                $nsName =
                    $this->parentNode->isSameNode($documentElement)
                    || $this->form == 'qualified'
                    || (
                        $this->localName == 'attribute'
                        && $documentElement->attributeFormDefault
                        == 'qualified'
                    )
                    || (
                        $this->localName == 'element'
                        && $documentElement->elementFormDefault
                        == 'qualified'
                    )
                    ? $documentElement->targetNamespace
                    : null;

                    $this->xComponentName_ = new XName($nsName, $this->name);
            } else {
                /** - Else return `null`. */
                $this->xComponentName_ = null;
            }
        }

        return $this->xComponentName_;
    }

    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:label>` element for that language in some `\<xsd:appinfo>`
         * element, return its value.
         */
        if (isset($lang)) {
            $labelElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:label[@xml:lang = '$lang']"
            )[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $labelElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:label[not(@xml:lang)]"
            )[0];

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
         * first `\<rdfs:label>` found in an `\<xsd:appinfo>` element,
         * regardless of its language. Thus, the schema author decides about
         * the default fallback language by putting the corresponding label in
         * the first place.
         */
        if (!isset($lang) || $fallbackFlags & self::FALLBACK_TO_OTHER_LANG) {
            $labelElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:label"
            )[0];

            if (isset($labelElement)) {
                return $labelElement->nodeValue;
            }
        }

        /**
         * - Otherwise, if the present element has an extended name and
         * $fallbackFlags contains GetLabelInterface::FALLBACK_TO_NAME, use
         * its local part as a fallback.
         */
        if ($fallbackFlags & self::FALLBACK_TO_NAME) {
            $xName = $this->getComponentXName();

            return isset($xName) ? $xName->getLocalName() : null;
        }

        /** - Otherwise return `null`. */
        return null;
    }

    public function getComment(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /**
         * - If a specific language is requested and there is an
         * `\<rdfs:comment>` element for that language in some `\<xsd:appinfo>`
         * element, return its value.
         */
        if (isset($lang)) {
            $commentElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:comment[@xml:lang = '$lang']"
            )[0];

            if (isset($commentElement)) {
                return $commentElement->nodeValue;
            }

            /* If there is no element with an explicit corresponding language,
             * look for one that inherits the language. */
            $commentElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:comment[not(@xml:lang)]"
            )[0];

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
         * first `\<rdfs:comment>` found in an `\<xsd:appinfo>` element,
         * regardless of its language. Thus, the schema author decides about
         * the default fallback language by putting the corresponding comment in
         * the first place.
         */
        if (!isset($lang) || $fallbackFlags & self::FALLBACK_TO_OTHER_LANG) {
            $commentElement = $this->query(
                "xsd:annotation/xsd:appinfo/rdfs:comment"
            )[0];

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
