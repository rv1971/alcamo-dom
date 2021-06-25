<?php

namespace alcamo\dom;

use alcamo\collection\ReadonlyCollection;
use GuzzleHttp\Psr7\{Uri, UriResolver};

/// Array of DOM documents indexed by dc:identifier.
class Documents extends ReadonlyCollection
{
    public const FACTORY_CLASS = DocumentFactory::class;

    public static function newFromGlob(
        string $pattern,
        ?int $libXmlOptions = null
    ): self {
        $docs = [];

        foreach (glob($pattern, GLOB_NOSORT | GLOB_BRACE) as $path) {
            $doc = self::createDocumentFromUrl($path, $libXmlOptions);

            $key = $doc->documentElement->getAttributeNS(
                Document::NS['dc'],
                'identifier'
            );

            if ($key === '') {
                $key = pathinfo($path, PATHINFO_FILENAME);
            }

            $docs[$key] = $doc;
        }

        return new self($docs);
    }

    public static function newFromUrls(
        iterable $urls,
        $baseUrl = null,
        ?int $libXmlOptions = null
    ): self {
        $docs = [];

        if (isset($baseUrl) && !($baseUrl instanceof Uri)) {
            $baseUrl = new Uri((string)$baseUrl);
        }

        foreach ($urls as $url) {
            if (!($url instanceof Uri)) {
                $url = new Uri((string)$url);
            }

            if (isset($baseUrl)) {
                $url = UriResolver::resolve($baseUrl, $url);
            }

            $doc = self::createDocumentFromUrl($url, $libXmlOptions);

            $key = $doc->documentElement->getAttributeNS(
                Document::NS['dc'],
                'identifier'
            );

            if ($key === '') {
                $key = pathinfo($url->getPath(), PATHINFO_FILENAME);
            }

            $docs[$key] = $doc;
        }

        return new self($docs);
    }

    public static function createDocumentFromUrl(
        string $url,
        ?int $libXmlOptions = null
    ): Document {
        $class = static::FACTORY_CLASS;

        return (new $class())->createFromUrl($url, null, $libXmlOptions);
    }

    /**
     * If a key in $docs is a string, use it as key int he result
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
                    Document::NS['dc'],
                    'identifier'
                );

            $docs2[$key] = $doc;
        }

        parent::__construct($docs2);
    }
}
