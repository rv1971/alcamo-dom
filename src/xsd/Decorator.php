<?php

namespace alcamo\dom\xsd;

use alcamo\dom\{GetCommentInterface, GetLabelInterface};
use alcamo\dom\decorated\{AbstractDecorator, GetLabelTrait, GetCommentTrait};
use alcamo\xml\XName;

/// Decorator providing getLabel()
class Decorator extends AbstractDecorator implements
    GetCommentInterface,
    GetLabelInterface
{
    use GetLabelTrait;
    use GetCommentTrait;

    /// Relative XPath to <rdfs:label> elements
    protected const RDFS_LABEL_XPATH = 'xsd:annotation/xsd:appinfo/rdfs:label';

    /// Relative XPath to <rdfs:comment> elements
    protected const RDFS_COMMENT_XPATH =
        'xsd:annotation/xsd:appinfo/rdfs:comment';

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
}
