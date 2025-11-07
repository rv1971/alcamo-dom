<?php

namespace alcamo\dom;

use alcamo\collection\PreventWriteArrayAccessTrait;
use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};

class DocumentCache implements \ArrayAccess
{
    use PreventWriteArrayAccessTrait;

    private static $instance_;

    public static function getInstance(): self
    {
        return self::$instance_ ?? (self::$instance_ = new self());
    }

    /// Array mapping absolute URLs to Document objects
    private $cache_ = [];

    public function offsetExists($url): bool
    {
        return isset($this->cache_[(string)$url]);
    }

    public function offsetGet($url): ?Document
    {
        return $this->cache_[(string)$url] ?? null;
    }

    /**
     * @brief Add a document to the cache
     *
     * @return Whether the document was actually added. `false` if it was
     * already in the cache.
     */
    public function add(Document $doc): bool
    {
        $url = new Uri($doc->documentURI);

        if (!Uri::isAbsolute($url)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a document with a non-absolute URL. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $doc->documentURI ]);
        }

        // normalize URL for use in caching
        $doc->documentURI = (string)UriNormalizer::normalize($url);

        if (isset($this->cache_[$doc->documentURI])) {
            if ($this->cache_[$doc->documentURI] !== $doc) {
                /** @throw alcamo::exception::ReadonlyViolation when
                 * attempting to replace a cache entry with a different
                 * document. */
                throw (new ReadonlyViolation())->setMessageContext(
                    [
                        'extraMessage' => 'attempt to replace cache entry '
                        . "\"{$doc->documentURI}\" "
                        . 'by a different document'
                    ]
                );
            }

            return false;
        }

        $this->cache_[$doc->documentURI] = $doc;

        return true;
    }
}
