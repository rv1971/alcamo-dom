<?php

namespace alcamo\dom;

/**
 * @brief Implementation of DomNodeInterface
 *
 * @date Last reviewed 2025-12-09
 */
trait DomNodeTrait
{
    use HavingBaseUriTrait;
    use Rfc5147Trait;

    /**
     * @brief Create a prefix for a given namespace name
     *
     * @note To be called only if lookupPrefix() returns `null`.
     *
     * @return Canonical prefix taken from
     * alcamo::xml::NamespaceMapsInterface, if there is one and it is not yet
     * in use. Otherwise a prefix of the form ns*n* with the lowest number *n*
     * not yet in use. In bot cases, create the corresponding namespace node.
     */
    public function createNsPrefix(string $nsName): string
    {
        $nsPrefix = $this->ownerDocument::NS_NAME_TO_NS_PREFIX[$nsName] ?? null;

        if (!isset($nsPrefix) || $this->lookupNamespaceURI($nsPrefix)) {
            for ($i = 1; $this->lookupNamespaceURI("ns$i"); $i++);

            $nsPrefix = "ns$i";
        }

        $this->setAttributeNS(Document::XMLNS_NS, "xmlns:$nsPrefix", $nsName);

        return $nsPrefix;
    }
}
