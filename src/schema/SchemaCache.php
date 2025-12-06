<?php

namespace alcamo\dom\schema;

use alcamo\dom\CacheTrait;
use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};
use Psr\Http\Message\UriInterface;

/**
 * @brief Cache for XML schemas
 */
class SchemaCache implements \ArrayAccess, \Countable
{
    use CacheTrait;

    /**
     * @brief Create a key for use in this cache
     *
     * @param $items Iterable of URIs or DOM documents
     */
    public function createKey(iterable $items): string
    {
        $uris = [];

        foreach ($items as $item) {
            switch (true) {
                case $item instanceof UriInterface:
                    $uri = $item;
                    break;

                case $item instanceof \DOMDocument:
                    $uri = new Uri($item->documentURI);
                    break;

                default:
                    $uri = new Uri((string)$item);
            }

            if (!Uri::isAbsolute($uri)) {
                /** @throw alcamo::exception::AbsoluteUriNeeded when
                 * encountering a non-absolute URI. */
                throw (new AbsoluteUriNeeded())
                    ->setMessageContext(['uri' => $uri ]);
            }

            $uris[] = (string)UriNormalizer::normalize($uri);
        }

        sort($uris);

        return implode(' ', $uris);
    }

    /**
     * @brief Add a schema to the cache
     *
     * @return Whether the schema was actually added. `false` if it was
     * already in the cache.
     */
    public function add(Schema $schema): bool
    {
        $key = $schema->getCacheKey();

        if (isset($this->data_[$key])) {
            if ($this->data_[$key] !== $schema) {
                /** @throw alcamo::exception::ReadonlyViolation when
                 * attempting to replace a cache entry with a different
                 * schema. */
                throw (new ReadonlyViolation())->setMessageContext(
                    [
                        'extraMessage' => 'attempt to replace cache entry '
                        . "\"$key\" by a different schema"
                    ]
                );
            }

            return false;
        }

        $this->data_[$key] = $schema;

        return true;
    }
}
