<?php

namespace alcamo\dom\xsd;

use alcamo\dom\extended\Element as BaseElement;
use alcamo\xml\XName;

/**
 * @brief Element class for use in DOMDocument::registerNodeClass()
 *
 * @date Last reviewed 2021-07-09
 */
class Element extends BaseElement
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
}
