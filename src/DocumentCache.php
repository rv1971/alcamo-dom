<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};

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
     * Unlike the ArrayAccess methods, this method checks whether the
     * document's URI is absolute and normalizes it.
     *
     * @return Whether the document was actually added. `false` if it was
     * already in the cache.
     */
    public function add(Document $doc): bool
    {
        $uri = new Uri($doc->documentURI);

        if (!Uri::isAbsolute($uri)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a document with a non-absolute URI. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $doc->documentURI ]);
        }

        /* Normalize URI for use in caching. */
        $doc->documentURI = (string)UriNormalizer::normalize($uri);

        if (isset($this->data_[$doc->documentURI])) {
            if ($this->data_[$doc->documentURI] !== $doc) {
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

        $this->data_[$doc->documentURI] = $doc;

        return true;
    }
}
