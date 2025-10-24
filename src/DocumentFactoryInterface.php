<?php

namespace alcamo\dom;

/**
 * @brief Class able to create a document from a URL
 *
 * @date Last reviewed 2025-09-15
 */
interface DocumentFactoryInterface
{
    /**
     * @brief Create a document from a URL
     *
     * @param $url string|UriInterface URL to get the data from.
     *
     * @param $class Explicit PHP class to use for the new document.
     *
     * @param $useCache
     * - If `true`, use the cache.
     * - If `false`, do not use the cache.
     * - If `null`, use the cache iff $url is absolute.
     *
     * @param $loadFlags OR-Combination of the load constants in class
     * Document.
     *
     * @param $libXmloptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load).
     */
    public function createFromUrl(
        $url,
        ?string $class = null,
        ?bool $useCache = null,
        ?int $loadFlags = null,
        ?int $libXmloptions = null
    ): Document;
}
