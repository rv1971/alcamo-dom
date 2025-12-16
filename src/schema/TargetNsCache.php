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
class TargetNsCache implements
    \ArrayAccess,
    \Countable,
    NamespaceConstantsInterface
{
    use CacheTrait {
        init as cacheTraitInit;
    }

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
     * @brief Get the target namespace (potentially `null`) of the XSD at $uri
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

        /** Normalize $uri for use in caching. */
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
     * @brief Create the extended name of a type from a type URI
     *
     * This implementation supports datatype URIs
     * - for XML Schema built-in datatypes through the notation
     *   `http://www.w3.org/2001/XMLSchema#xxx` to refer to built-in type
     *   `xxx`
     * - for simple types defined in XSDs through the notation
     *   `http://example.org#xxx` to refer to the simple type with ID `xxx` in
     *   the XSD located at http://example.org.
     *
     * Despite the syntactic similarity, there are two completely different
     * concepts behind. The first is a convention to refer to XML Schema
     * built-in datatypes by *name,* *without* actually accessing an
     * XSD. Indeed, there is no XSD actually located at the URI. The second
     * actually *accesses* an XSD and finds a type by *ID,* even though it is
     * highly recommanded that the ID is identical to the name.
     *
     * This has lots of implications. For example, the XHTML datatypes
     * *cannot* be used this way because the
     * [official XSD](https://www.w3.org/MarkUp/SCHEMA/xhtml-datatypes-1.xsd)
     * lacks IDs. Sadly, this implies that the very useful CURIE-related XHTML
     * datatypes are not available through this mechanism.
     *
     * The present code implements the first case *as if* there were an XSD at
     * the indicated location, and the second case *as if* it were garanteed
     * that IDs and names always coincide.
     *
     * @sa [Datatype IRIs](https://www.w3.org/TR/2014/REC-rdf11-concepts-20140225/#datatype-iris)
     * @sa [User Defined Datatypes](https://www.w3.org/TR/swbp-xsch-datatypes/#sec-userDefined)
     */
    public function typeUriToTypeXName(string $uri): XName
    {
        [ $nsUri, $localName ] = explode('#', $uri, 2);

        return new XName($this[$nsUri], $localName);
    }

    /**
     * Initialize with a mapping of the XSD namespace to itself, to support
     * the first case explained in typeUriToTypeXName().
     */
    public function init(): void
    {
        $this->cacheTraitInit();

        $this->data_[self::XSD_NS] = self::XSD_NS;
    }
}
