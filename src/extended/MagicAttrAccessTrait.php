<?php

namespace alcamo\dom\extended;

use alcamo\exception\UnknownNamespacePrefix;
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

        $attrNode = $this->getAttrNode($attrName, $attrName2);

        /* Return null if there is no such node. */
        $value = isset($attrNode) ? $attrNode->getValue() : null;

        if (isset($attrName2)) {
            $this->attrCache_[$attrName2] = $value;
        }

        return $this->attrCache_[$attrName] = $value;
    }

    /// Set an attribute in the document and the attribute cache
    public function __set(string $attrName, $value): void
    {
        /** Setting an attribute to `null` calls __unset(). */
        if (!isset($value)) {
            $this->__unset($attrName);
            return;
        }

        $attrNode =
            $this->getAttrNode($attrName, $attrName2, $nsName, $localName);

        if (isset($attrNode)) {
            $attrNode->unregister();
        }

        if (isset($nsName)) {
            $nsPrefix = $this->lookupPrefix($nsName);

            if (!isset($nsPrefix)) {
                $nsPrefix = $this->createNsPrefix($nsName);
            }

            $attrNode = $this->ownerDocument
                ->createAttributeNS($nsName, "$nsPrefix:$localName");

            $this->setAttributeNodeNS($attrNode);
        } else {
            $attrNode = $this->ownerDocument->createAttribute($attrName);

            $this->setAttributeNode($attrNode);
        }

        $attrNode->value = $value;

        $this->attrCache_[$attrName] = $attrNode->getValue();

        if (isset($attrName2)) {
            $this->attrCache_[$attrName2] = $attrNode->getValue();
        }
    }

    /// Unset an attribute in the document and the attribute cache
    public function __unset(string $attrName): void
    {
        $attrNode =
            $this->getAttrNode($attrName, $attrName2, $nsName, $localName);

        if (!isset($attrNode)) {
            return;
        }

        $attrNode->unregister();

        if (isset($nsName)) {
            $this->removeAttributeNS($nsName, $localName);
        } else {
            $this->removeAttribute($attrName);
        }

        $this->attrCache_[$attrName] = null;

        if (isset($attrName2)) {
            $this->attrCache_[$attrName2] = null;
        }
    }

    /**
     * @brief Get attribute node
     *
     * @param $attrName local name, qualified name or extended name
     * (i.e. string representation of alcamo::xml::XName, i.e. concatenation
     * of namespace name, one space, and local name).
     *
     * @param $attrName2 [out]
     * - extended name if $attrName is a qualified name.
     * - qualified name if $attrName is an extended name and a mapping of
     *   the namespace name to a namespace prefix exists.
     * - otherwise `null`
     *
     * @return Attribute node or `null`.
     *
     * @note The namespace prefix in a qualified name is interpreted based on
     * the maps in alcamo::xml::NamespaceMapsInterface, *not* based on
     * the namespace mappings in the document.
     *
     * @note It is possible to use an extended name to (attempt to) access an
     * attribute even if no mapping of the namespace name to a namepsace
     * prefix exists in
     * alcamo::xml::NamespaceMapsInterface::NS_NAME_TO_NS_PREFIX. The converse
     * is not possible.
     */
    public function getAttrNode(
        string $attrName,
        &$attrName2 = null,
        &$nsName = null,
        &$localName = null
    ): ?Attr {
        $hasSpace = strpos($attrName, ' ');
        $hasColon = $hasSpace ? false : strpos($attrName, ':');

        if (!$hasSpace && !$hasColon) {
            $attrname2 = null;
            $nsName = null;
            $localName = $attrName;

            return $this->hasAttribute($attrName)
                ? $this->getAttributeNode($attrName)
                : null;
        }

        if ($hasColon) {
            [ $nsPrefix, $localName ] = explode(':', $attrName);

            $nsName =
                $this->ownerDocument::NS_PRFIX_TO_NS_NAME[$nsPrefix] ?? null;

            if (!isset($nsName)) {
                /** @throw alcamo::exception::UnknownNamespacePrefix if the
                 *  prefix is not found in the map. */
                throw (new UnknownNamespacePrefix())->setMessageContext(
                    [
                        'prefix' => $nsPrefix,
                        'inData' => $attrName
                    ]
                );
            }

            $attrName2 = "$nsName $localName";
        } else {
            [ $nsName, $localName ] = explode(' ', $attrName);

            $nsPrefix =
                $this->ownerDocument::NS_NAME_TO_NS_PREFIX[$nsName] ?? null;

            if (isset($nsPrefix)) {
                $attrName2 = "$nsPrefix:$localName";
            }
        }

        return $this->hasAttributeNS($nsName, $localName)
            ? $this->getAttributeNodeNS($nsName, $localName)
            : null;
    }
}
