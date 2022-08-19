<?php

namespace alcamo\dom;

/**
 * @brief Class able to create a document from a URL
 *
 * @date Last reviewed 2021-06-30
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
     */
    public function createFromUrl(
        string $url,
        ?string $class = null,
        ?int $libXmlOptions = null,
        ?bool $useCache = null,
        ?int $loadFlags = null
    ): Document;
}
