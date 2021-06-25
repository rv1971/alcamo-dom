<?php

namespace alcamo\dom\xsd;

use alcamo\dom\extended\Element as BaseElement;
use alcamo\xml\XName;

class Element extends BaseElement
{
    private $xComponentName_ = false; ///< ?XName

    public function getComponentXName(): ?XName
    {
        if ($this->xComponentName_ === false) {
            /* Since offsetGet() is called, conservation of this derived
             * object is already ensured. */

            if (isset($this->ref)) {
                $this->xComponentName_ = $this->ref;
            } elseif (isset($this->name)) {
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
                $this->xComponentName_ = null;
            }
        }

        return $this->xComponentName_;
    }
}
