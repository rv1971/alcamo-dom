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
     * $doc->getIdentifierUri() is used as a cache key.
     *
     * @return Whether the document was actually added. `false` if it was
     * already in the cache.
     */
    public function add(Document $doc): bool
    {
        $key = $doc->getIdentifierUri();

        if (!isset($key)) {
            /** @throw alcamo::exception::AbsoluteUriNeeded when attempting to
             * cache a document with a non-absolute URI. */
            throw (new AbsoluteUriNeeded())
                ->setMessageContext([ 'uri' => $doc->documentURI ]);
        }

        if (isset($this->data_[$key])) {
            if ($this->data_[$key] !== $doc) {
                /** @throw alcamo::exception::ReadonlyViolation when
                 * attempting to replace a cache entry with a different
                 * document. */
                throw (new ReadonlyViolation())->setMessageContext(
                    [
                        'extraMessage' => 'attempt to replace cache entry '
                        . "\"{$key}\" "
                        . 'by a different document'
                    ]
                );
            }

            return false;
        }

        $this->data_[$key] = $doc;

        return true;
    }
}
