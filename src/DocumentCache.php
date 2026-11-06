<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};
use Psr\Http\Message\UriInterface;

/**
 * @brief Cache for DOM documents
 *
 * Data is accessed through the readonly ArrayAccess interface via normalized
 * absolute URIs as keys. Other keys will not find any data.
 */
class DocumentCache implements \ArrayAccess, \Countable
{
    use CacheTrait;

    /**
     * @brief Add a document to the cache
     *
     * @param $doc Document object to cache
     *
     * @param $uri Uri to use as a cache key. If not given, $doc->documentURI
     * is used. Unlike the ArrayAccess methods, this method checks whether
     * the document's URI is absolute and normalizes it.
     *
     * @return Whether the document was actually added. `false` if it was
     * already in the cache.
     *
     * If $uri is not given, $doc->documentURI is normalized, otherwise it is
     * left unchanged.
     */
    public function add(Document $doc, $uri = null): bool
    {
        $isCustomUri = isset($uri);

        if (!$isCustomUri) {
            $uri = new Uri($doc->documentURI);
        } elseif (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if (!Uri::isAbsolute($uri)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a document with a non-absolute URI. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => (string)$uri ]);
        }

        /* Normalize URI for use in caching. */
        $uriString = (string)UriNormalizer::normalize($uri);

        if (isset($this->data_[$uriString])) {
            if ($this->data_[$uriString] !== $doc) {
                /** @throw alcamo::exception::ReadonlyViolation when
                 * attempting to replace a cache entry with a different
                 * document. */
                throw (new ReadonlyViolation())->setMessageContext(
                    [
                        'extraMessage' => 'attempt to replace cache entry '
                        . "\"{$uriString}\" "
                        . 'by a different document'
                    ]
                );
            }

            return false;
        }

        if (!$isCustomUri) {
            $doc->documentURI = $uriString;
        }

        $this->data_[$uriString] = $doc;

        return true;
    }
}
