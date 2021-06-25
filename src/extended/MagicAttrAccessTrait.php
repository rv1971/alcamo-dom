<?php

namespace alcamo\dom\extended;

use alcamo\xml\XName;

/**
 * @brief Access to attributes as if they were object properties
 *
 * There are three ways to specify an attribute:
 * - Attribute name without namespace prefix.
 * - Qualified name with a prefix registered in the NS constant of the
 *    document class.
 * - Serialization of an XName object.
 * Hence there may be more than one way to specify the same attribute. All
 * ways to specify an attribute are equally stored in the cache.
 */
trait MagicAttrAccessTrait
{
    private $attrCache_ = []; ///< Map of attributes to values.

    public function __isset($attrName)
    {
        /* At first look in the cache. `array_key_exists()` must be used
         * instead of `isset()` because an attribute might evaluate to `null`
         * even though it is present, in which case `offsetExists()` must
         * return true. */
        if (array_key_exists($attrName, $this->attrCache_)) {
            return true;
        }

        /* If not found in the cache, check which kind of attribute name is
         * given. */
        if (strpos($attrName, ' ') === false) {
            if (strpos($attrName, ':') === false) {
                return $this->hasAttribute($attrName);
            } else {
                $a = explode(':', $attrName, 2);

                return $this->hasAttributeNS(
                    $this->ownerDocument::NS[$a[0]],
                    $a[1]
                );
            }
        } else {
            return $this->hasAttributeNS(...explode(' ', $attrName, 2));
        }
    }

    public function __get($attrName)
    {
        /* At first look in the cache. `array_key_exists()` must be used
         * instead of `isset()` because an attribute might evaluate to
         * `null`. */
        if (array_key_exists($attrName, $this->attrCache_)) {
            return $this->attrCache_[$attrName];
        }

        if (strpos($attrName, ' ') === false) {
            if (strpos($attrName, ':') === false) {
                $attrNode = $this->getAttributeNode($attrName);
            } else {
                $a = explode(':', $attrName, 2);

                $attrNode = $this->getAttributeNodeNS(
                    $this->ownerDocument::NS[$a[0]],
                    $a[1]
                );
            }
        } else {
            $attrNode = $this->getAttributeNodeNS(...explode(' ', $attrName));
        }

        if (!$attrNode) {
            return null;
        }

        if (!$this->attrCache_) {
            /* Ensure conservation of the derived object when putting the
             * first entry into the cache. */
            $this->register();
        }

        return ($this->attrCache_[$attrName] = $attrNode->getValue());
    }
}
