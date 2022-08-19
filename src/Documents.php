<?php

namespace alcamo\dom;

use alcamo\collection\ReadonlyCollection;
use GuzzleHttp\Psr7\{Uri, UriResolver};
use Psr\Http\Message\UriInterface;

/**
 * @brief Array of DOM documents indexed by their dc:identifier
 *
 * Features caching as well as automatic determination of the document class.
 *
 * @date Last reviewed 2021-07-01
 */
class Documents extends ReadonlyCollection
{
    /// PHP factory class used to create documents
    public const FACTORY_CLASS = DocumentFactory::class;

    /**
     * @brief Create documents from a glob() pattern
     *
     * @param $pattern Pattern for [glob()](https://www.php.net/manual/en/function.glob)
     *
     * @param $globFlags Flags for glob(). Defaults to `GLOB_NOSORT |
     * GLOB_BRACE`.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public static function newFromGlob(
        string $pattern,
        ?int $globFlags = null,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): self {
        /** Use the createFromUrl() method of an instance of @ref
         *  FACTORY_CLASS to create the documents. */
        $factoryClass = static::FACTORY_CLASS;

        $factory = new $factoryClass();

        $docs = [];

        foreach (
            glob($pattern, $globFlags ?? GLOB_NOSORT | GLOB_BRACE) as $path
        ) {
            $doc = $factory->createFromUrl(
                'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $path),
                null,
                $libXmlOptions,
                $loadFlags
            );

            /** If the document element has a `dc:identifier` attribute, use
             *  it for the array key. Otherwise use the file name. */
            $key = $doc->documentElement
                ->getAttributeNS(Document::DC_NS, 'identifier');

            if ($key === '') {
                $key = pathinfo($path, PATHINFO_FILENAME);
            }

            $docs[$key] = $doc;
        }

        return new static($docs);
    }

    /**
     * @brief Create documents from a collection of URLs
     *
     * @param $urls Collection of URLs.
     *
     * @param $baseUrl string|UriInterface Base URL to locate documents
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public static function newFromUrls(
        iterable $urls,
        $baseUrl = null,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): self {
        /** Use the createFromUrl() method of an instance of @ref
         *  FACTORY_CLASS to create the documents. */
        $factoryClass = static::FACTORY_CLASS;

        $factory = new $factoryClass();

        $docs = [];

        if (isset($baseUrl) && !($baseUrl instanceof UriInterface)) {
            $baseUrl = new Uri((string)$baseUrl);
        }

        foreach ($urls as $url) {
            if (!($url instanceof UriInterface)) {
                $url = new Uri((string)$url);
            }

            if (isset($baseUrl)) {
                $url = UriResolver::resolve($baseUrl, $url);
            }

            $doc = $factory
                ->createFromUrl($url, null, $libXmlOptions, $loadFlags);

            /** If the document element has a `dc:identifier` attribute, use
             *  it for the array key. Otherwise use the file name. */
            $key = $doc->documentElement
                ->getAttributeNS(Document::DC_NS, 'identifier');

            if ($key === '') {
                $key = pathinfo($url->getPath(), PATHINFO_FILENAME);
            }

            $docs[$key] = $doc;
        }

        return new static($docs);
    }

    /**
     * @brief Construct from a collection of documents
     *
     * If a key in $docs is a string, use it as key in the result
     * collection. Otherwise, use the `dc:identifier` attribute in the
     * document element.
     */
    public function __construct(iterable $docs)
    {
        $docs2 = [];

        foreach ($docs as $key => $doc) {
            $key = is_string($key)
                ? $key
                : $doc->documentElement->getAttributeNS(
                    Document::DC_NS,
                    'identifier'
                );

            $docs2[$key] = $doc;
        }

        parent::__construct($docs2);
    }
}
