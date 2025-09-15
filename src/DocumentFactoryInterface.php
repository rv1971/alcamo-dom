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
     * @param $url URL to get the data from.
     *
     * @param $class Document class to create.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     *
     * @param $useCache Whether to use a cached document, if any
     *
     * @param $loadFlags Flags passed to the document's load method
     */
    public function createFromUrl(
        string $url,
        ?string $class = null,
        ?int $libXmlOptions = null,
        ?bool $useCache = null,
        ?int $loadFlags = null
    ): Document;
}
