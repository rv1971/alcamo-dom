<?php

namespace alcamo\dom\extended;

use alcamo\xml\XName;

/**
 * @brief Provide access to attributes as if they were object properties
 *
 * There are three ways to specify an attribute as a property:
 * - Attribute name without namespace prefix.
 * - Qualified name with a prefix registered in the NS_PRFIX_TO_NS_NAME
 *   constant of the document class (which the document class inherits from
 *   alcamo::dom::NamespaceConstantsInterface).
 * - Serialization of an XName object.
 *
 * Hence there may be more than one way to specify the same attribute. All
 * ways to specify an attribute are equally stored in the cache.
 *
 * @note To change or unset the cached attributes (and the attributes in the
 * DOM document itself), use the set and unset mechanisms. Changing directly
 * the DOM document will not change the cached results.
 *
 * @date Last reviewed 2025-11-05
 */
trait MagicAttrAccessTrait
{
    private $attrCache_ = []; ///< Map of attributes to values

    /**
     * @brief Check whether an element has the requested attribute
     *
     * This calls __get() because __get() returns `null` even if the attribute
     * exists, in the case that the conversion function converts the attribute
     * value to `null`.
     *
     * If the attribute does not exist, calling __get() does not add much
     * procsssing time. If it exitsts, __get() calculates and caches its
     * value. This may add considerable processing time but ensures that the
     * next access via __isset() or __get() uses the cache and is therefore
     * fast.
     */
    public function __isset(string $attrName): bool
    {
        return $this->__get($attrName) !== null;
    }

    /**
     * @brief Return the result of Attr::getValue()
     *
     * When called a second time, the result is taken from a cache.
     */
    public function __get(string $attrName)
    {
        /* At first look in the cache. isset() is fast and works for all cases
         * where the attribute value is not `null`. To cover the latter as
         * well, the slower array_key_exists() is used. */
        if (
            isset($this->attrCache_[$attrName])
            || array_key_exists($attrName, $this->attrCache_)
        ) {
            return $this->attrCache_[$attrName];
        }

        if (!$this->attrCache_) {
            /* Ensure conservation of the derived object when putting the
             * first entry into the cache. */
            $this->register();
        }

        /* If not found in the cache, check which kind of attribute name is
         * given, and get the attribute node, if any. */
        if (strpos($attrName, ' ') === false) {
            if (strpos($attrName, ':') === false) {
                $attrNode = $this->getAttributeNode($attrName);
            } else {
                [ $nsPrefix, $localName ] = explode(':', $attrName, 2);

                $nsName = $this->ownerDocument::NS_PRFIX_TO_NS_NAME[$nsPrefix];

                $attrNode = $this->getAttributeNodeNS($nsName, $localName);

                $attrName2 = "$nsName $localName";
            }
        } else {
            [ $nsName, $localName ] = explode(' ', $attrName);

            $attrNode = $this->getAttributeNodeNS($nsName, $localName);

            $nsPrefix =
                $this->ownerDocument::NS_NAME_TO_NS_PREFIX[$nsName] ?? null;

            if (isset($nsPrefix)) {
                $attrName2 = "$nsPrefix:$localName";
            }
        }

        /* Return null if there is no such node. */
        $value = $attrNode ? $attrNode->getValue() : null;

        if (isset($attrName2)) {
            $this->attrCache_[$attrName2] = $value;
        }

        return $this->attrCache_[$attrName] = $value;
    }

    public function __unset(string $attrName): void
    {
        if (strpos($attrName, ' ') === false) {
            if (strpos($attrName, ':') === false) {
                $this->removeAttribute($attrName);
            } else {
                [ $nsPrefix, $localName ] = explode(':', $attrName, 2);

                $nsName = $this->ownerDocument::NS_PRFIX_TO_NS_NAME[$nsPrefix];

                $this->removeAttributeNS($nsName, $localName);

                $attrName2 = "$nsName $localName";
            }
        } else {
            [ $nsName, $localName ] = explode(' ', $attrName);

            $this->removeAttributeNS($nsName, $localName);

            $nsPrefix =
                $this->ownerDocument::NS_NAME_TO_NS_PREFIX[$nsName] ?? null;

            if (isset($nsPrefix)) {
                $attrName2 = "$nsPrefix:$localName";
            }
        }

        $this->attrCache_[$attrName] = null;

        if (isset($attrName2)) {
            $this->attrCache_[$attrName2] = null;
        }
    }
}
