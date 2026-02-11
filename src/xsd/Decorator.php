<?php

namespace alcamo\dom\xsd;

use alcamo\dom\decorated\HavingDocumentationDecorator;
use alcamo\xml\XName;

/**
 * @namespace alcamo::dom::xsd
 *
 * XSD-specific classes. Since this package supports use of XSD elements (such
 * as \<xsd:annotation>) within non-XSD documents, no XSD-specific document or
 * element class is provided. XSD-specific functionality is provided in
 * element decorators.
 */

/**
 * @brief Decorator providing, among others, the component's extended name, if
 * any
 *
 * @date Last reviewed 2025-11-05
 */
class Decorator extends HavingDocumentationDecorator
{
    public const APPINFO_XPATHS = [ 'xsd:annotation/xsd:appinfo' ];

    private $componentXName_ = false; ///< ?XName

    /**
     * @brief Get the component's extended name, if possible
     *
     * When calling this method a second time, the result is taken from the
     * cache.
     */
    public function getComponentXName(): ?XName
    {
        if ($this->componentXName_ === false) {
            /* The implicit call to offsetGet() in `$this->ref` includes a
             * call to
             * alcamo::dom::extended::RegisteredNodeTrait::register(). */

            if (isset($this->ref)) {
                /** - If there is a `ref` attribute, return its value. */
                $this->componentXName_ = $this->ref;
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

                    $this->componentXName_ = new XName($nsName, $this->name);
            } else {
                /** - Else return `null`. */
                $this->componentXName_ = null;
            }
        }

        return $this->componentXName_;
    }

    /** @copybrief alcamo::dom::decorated::HavingDocumentationDecorator */
    public function getLabel(
        ?string $lang = null,
        ?int $fallbackFlags = null
    ): ?string {
        /** - Use <rdfs:label> or rdfs:label attribute, if applicable. */
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
         * - Otherwise, if the present element has an extended name and
         * $fallbackFlags contains
         * HavingDocumentationInterface::FALLBACK_TO_NAME, use its local part
         * as a fallback.
         */
        if ($fallbackFlags & self::FALLBACK_TO_NAME) {
            $xName = $this->getComponentXName();

            return isset($xName) ? $xName->getLocalName() : null;
        }

        /** - Otherwise return `null`. */
        return null;
    }
}
