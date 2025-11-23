<?php

namespace alcamo\dom\extended;

use alcamo\xml\XName;

/**
 * @brief Provide access to attributes as if they were object properties
 *
 * There are three ways to specify an attribute as a property:
 * - Attribute name without namespace prefix.
 * - Qualified name with a prefix registered in the NS_PRFIX_TO_NS_URI
 *   constant of the document class (which the document class inherits from
 *   alcamo::dom::NamespaceConstantsInterface).
 * - Serialization of an XName object.
 *
 * Hence there may be more than one way to specify the same attribute. All
 * ways to specify an attribute are equally stored in the cache.
 *
 * @warning The cached attributes are never updated, not even when an attribute
 * is changed.
 *
 * @date Last reviewed 2025-11-05
 */
trait MagicAttrAccessTrait
{
    private $attrCache_ = []; ///< Map of attributes to values

    /**
     * @brief Check whether an element has the requested attribute
     *
     * Unlike __get(), this method does not compute the attribute value
     * and may therefore be significantly faster than __get().
     */
    public function __isset(string $attrName): bool
    {
        /* At first look in the cache. isset() is fast and works for all cases
         * where the attribute value is not `null`. To cover the latter as
         * well, the slower array_key_exists() is used. */
        if (
            isset($this->attrCache_[$attrName])
            || array_key_exists($attrName, $this->attrCache_)
        ) {
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
                    $this->ownerDocument::NS_PRFIX_TO_NS_URI[$a[0]],
                    $a[1]
                );
            }
        } else {
            return $this->hasAttributeNS(...explode(' ', $attrName, 2));
        }
    }

    /**
     * @brief Return the result of Attr::getValue()
     *
     * When called a second time, the result is taken from a cache.
     */
    public function __get(string $attrName)
    {
        /* At first look in the cache just as in __isset(). */
        if (
            isset($this->attrCache_[$attrName])
            || array_key_exists($attrName, $this->attrCache_)
        ) {
            return $this->attrCache_[$attrName];
        }

        /* If not found in the cache, check which kind of attribute name is
         * given, and get the attribute node, if any. */
        if (strpos($attrName, ' ') === false) {
            if (strpos($attrName, ':') === false) {
                $attrNode = $this->getAttributeNode($attrName);
            } else {
                $a = explode(':', $attrName, 2);

                $attrNode = $this->getAttributeNodeNS(
                    $this->ownerDocument::NS_PRFIX_TO_NS_URI[$a[0]],
                    $a[1]
                );
            }
        } else {
            $attrNode = $this->getAttributeNodeNS(...explode(' ', $attrName));
        }

        /* Return null if there is no such node. */
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
