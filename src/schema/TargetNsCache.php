<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    CacheTrait,
    DocumentCache,
    NamespaceConstantsInterface,
    ShallowDocument
};
use alcamo\exception\AbsoluteUriNeeded;
use alcamo\uri\{Uri, UriNormalizer};
use alcamo\xml\XName;
use Psr\Http\Message\UriInterface;

/**
 * @brief Cache for target namespaces of XSDs
 *
 * Data is accessed through the readonly ArrayAccess interface via absolute
 * URIs as keys.
 *
 * Non-normalized URIs can be used as keys since they are normalized in the
 * access methods. However, access via normalized URIs is more efficient since
 * they are directly found in the cache.
 */
class TargetNsCache implements \ArrayAccess, NamespaceConstantsInterface
{
    use CacheTrait;

    /**
     * @brief Whether the XSD at $uri has a target namespace
     *
     * If the XSD is not found in the cache, add it to the cache.
     */
    public function offsetExists($uri): bool
    {
        if (!isset($this->data_[(string)$uri])) {
            $this->add($uri);
        }

        /* Internally, an XSD with no target namespace is assigned the value
         * `false`. Here, $this->data_[$uri] is known to be set, otherwise the
         * above call to add() would have thrown an exception. Hence
         * $this->data_[$uri] is either a target namespace (which always
         * evaluates to `true`) or the value `false`. */
        return $this->data_[(string)$uri];
    }

    /**
     * @brief Get the target namespace (potentially null) of the XSD at $uri
     *
     * If not found in the cache, add it to the cache.
     */
    public function offsetGet($uri): ?string
    {
        /* This uses the above offsetExists(). */
        if (!isset($this[(string)$uri])) {
            $this->add($uri);
        }

         /* Internally, an XSD with no target namespace is assigned the value
          * `false`. Here, $this->data_[$uri] is known to be set, otherwise
          * the above call to add() would have thrown an exception. Hence
          * $this->data_[$uri] is either a target namespace or the value
          * `false`. The latter is returned as `null`. */
        return $this->data_[(string)$uri] ?: null ;
    }

    /**
     * @brief Add the target namespace of the XSD at $uri to the cache
     *
     * @return Whether the URI was actually added. `false` if it was
     * already in the cache.
     */
    public function add(&$uri): bool
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if (!Uri::isAbsolute($uri)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a non-absolute URI. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $uri ]);
        }

        /* Normalize URI for use in caching. */
        $uri = (string)UriNormalizer::normalize($uri);

        if (isset($this->data_[$uri])) {
            return false;
        }

        $doc = isset(DocumentCache::getInstance()[$uri])
            ? DocumentCache::getInstance()[$uri]
            : ShallowDocument::newFromUri($uri);

        $this->data_[$uri] =
            $doc->documentElement->hasAttribute('targetNamespace')
            ? $doc->documentElement->getAttribute('targetNamespace')
            : false;

        return true;
    }

    /**
     */
    public function typeUriToTypeXName(string $uri): XName
    {
        [ $nsUri, $localName ] = explode('#', $uri);

        return new XName($this[$nsUri], $localName);
    }

    /**
     * Initialize with normalized namespaces from NS_PRFIX_TO_NS_URI.
     */
    protected function __construct()
    {
        foreach (static::NS_PRFIX_TO_NS_URI as $nsName) {
            $this->data_[(string)UriNormalizer::normalize(new Uri($nsName))] =
                $nsName;
        }
    }
}
