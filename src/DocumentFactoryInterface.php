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
     * @param $class PHP class to use for the new document. If `null`,
     * urlToClass() is called to get the class.
     *
     * @param $useCache ?bool
     * - if `true`, use the cache
     * - if `false`, do not use the cache
     * - if `null`, use the cache iff $url is absolute
     *
     * @param $loadFlags OR-Combination of the load constants in class
     * Document. If not given, getLoadFlags() is used.
     *
     * @param $libXmloptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load). If
     * not given, getLibxmlOptions() is used.
     */
    public function createFromUrl(
        $url,
        ?string $class = null,
        ?bool $useCache = null,
        ?int $loadFlags = null,
        ?int $libXmloptions = null
    ): Document;
}
