<?php

namespace alcamo\dom;

/**
 * @brief Class able to create a document from a URI
 *
 * @date Last reviewed 2025-09-15
 */
interface DocumentFactoryInterface extends NamespaceConstantsInterface
{
    /**
     * @brief Create a document or element from a URI
     *
     * @param $uri string|UriInterface URI to get the data from.
     *
     * @param $class Explicit PHP class to use for the new document.
     *
     * @param $useCache
     * - If `true`, use the cache.
     * - If `false`, do not use the cache.
     * - If `null`, use the cache iff $uri is absolute.
     *
     * @param $loadFlags OR-Combination of the load constants in the
     * alcamo::dom::Document class. If not given, getLoadFlags() is used.
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load). If
     * not given, getLibxmlOptions() is used.
     *
     * @return
     * - If $uri has no fragment, an alcamo::dom::Document.
     * - Otherwise, if $uri has a fragment and the document contains an
     *   element with an ID equal to the fragment, that element.
     * - Otherwise `null`.
     */
    public function createFromUri(
        $uri,
        ?string $class = null,
        ?bool $useCache = null,
        ?int $loadFlags = null,
        ?int $libXmloptions = null
    ): ?\DOMNode;
}
