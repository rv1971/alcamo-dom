<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};

/**
 * @brief Cache for DOM documents
 */
class DocumentCache implements \ArrayAccess
{
    use CacheTrait;

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
