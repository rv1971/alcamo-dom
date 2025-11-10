<?php

namespace alcamo\dom\schema;

use alcamo\dom\{CacheTrait, DocumentCache, ShallowDocument};
use alcamo\exception\AbsoluteUriNeeded;
use alcamo\uri\{Uri, UriNormalizer};
use Psr\Http\Message\UriInterface;

/**
 * @brief Cache for target namespaces of XSDs
 *
 * Data is accessed through the readonly ArrayAccess interface via absolute
 * URIs as keys.
 */
class TargetNsCache implements \ArrayAccess
{
    use CacheTrait;

    /**
     * @brief Whether the XSD at $url has a target namespace
     *
     * If the XSD is not found in the cache, add it to the cache.
     */
    public function offsetExists($url): bool
    {
        if (!isset($this->data_[(string)$url])) {
            $this->add($url);
        }

        /* Internally, an XSD with no target namespace is assigned the value
         * `false`. Here, $this->data_[$url] is known to be set, otherwise the
         * above call to add() would have thrown an exception. Hence
         * $this->data_[$url] is either a target namespace (which always
         * evaluates to `true`) or the value `false`. */
        return $this->data_[(string)$url];
    }

    /**
     * @brief Get the target namespace (potentially null) of the XSD at $url
     *
     * If not found in the cache, add it to the cache.
     */
    public function offsetGet($url): ?string
    {
        /* This uses the above offsetExists(). */
        if (!isset($this[(string)$url])) {
            $this->add($url);
        }

         /* Internally, an XSD with no target namespace is assigned the value
          * `false`. Here, $this->data_[$url] is known to be set, otherwise
          * the above call to add() would have thrown an exception. Hence
          * $this->data_[$url] is either a target namespace or the value
          * `false`. The latter is returned as `null`. */
        return $this->data_[(string)$url] ?: null ;
    }

    /**
     * @brief Add the target namespace of the XSD at $url to the cache
     *
     * Unlike the ArrayAccess methods, this method checks whether $url is
     * absolute and normalizes it.
     *
     * @return Whether the URL was actually added. `false` if it was
     * already in the cache.
     */
    public function add(&$url): bool
    {
        if (!($url instanceof UriInterface)) {
            $url = new Uri($url);
        }

        if (!Uri::isAbsolute($url)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a non-absolute URL. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $url ]);
        }

        /* Normalize URL for use in caching. */
        $url = (string)UriNormalizer::normalize($url);

        if (isset($this->data_[$url])) {
            return false;
        }

        $doc = isset(DocumentCache::getInstance()[$url])
            ? DocumentCache::getInstance()[$url]
            : ShallowDocument::newFromUrl($url);

        $this->data_[$url] =
            $doc->documentElement->hasAttribute('targetNamespace')
            ? $doc->documentElement->getAttribute('targetNamespace')
            : false;

        return true;
    }
}
