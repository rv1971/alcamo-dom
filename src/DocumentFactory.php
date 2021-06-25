<?php

namespace alcamo\dom;

use alcamo\ietf\Uri;
use alcamo\dom\xsd\Document as Xsd;
use alcamo\exception\{AbsoluteUriNeeded, Locked};
use GuzzleHttp\Psr7\{UriNormalizer, UriResolver};

class DocumentFactory implements DocumentFactoryInterface
{
    public const NS_NAME_TO_CLASS = [
        Document::NS['xsd'] => Xsd::class
    ];

    public const DEFAULT_CLASS = Document::class;

    private static $cache_; ///< Array mapping URLs to Document objects

    public static function addToCache(Document $doc)
    {
        $url = new Uri($doc->documentURI);

        if (!Uri::isAbsolute($url)) {
            /** @throw AbsoluteUriNeeded when attempting to use a
             * non-absolute URL as a cache key. */
            throw new AbsoluteUriNeeded($doc->documentURI);
        }

        // normalize URL for use in caching
        $doc->documentURI = (string)UriNormalizer::normalize($url);

        if (isset(self::$cache_[$doc->documentURI])) {
            if (self::$cache_[$doc->documentURI] !== $doc) {
                /** @throw Locked when attempting to replace a cache entry
                 * with a different document. */
                throw new Locked(
                    $doc,
                    "Attempt to replace cache entry \"{$doc->documentURI}\" "
                    . "with a different document"
                );
            }
        } else {
            self::$cache_[$doc->documentURI] = $doc;
        }
    }

    private $baseUrl_; ///< UriInterface

    public function __construct(?string $baseUrl = null)
    {
        if (isset($baseUrl)) {
            $this->baseUrl_ = new Uri($baseUrl);
        }
    }

    public function getBaseUrl(): ?Uri
    {
        return $this->baseUrl_;
    }

    /**
     * @param $useCache ?bool
     * - if `true`, use the cache
     * - if `false`, do not use the cache
     * - if `null`, use the cache iff $url is absolute
     */
    public function createFromUrl(
        string $url,
        ?string $class = null,
        ?int $libXmlOptions = null,
        ?bool $useCache = null
    ): Document {
        $url = new Uri($url);

        if (isset($this->baseUrl_)) {
            $url = UriResolver::resolve($this->baseUrl_, $url);
        }

        if (!isset($class)) {
            $class = $this->urlToClass($url);
        }

        if ($useCache !== false) {
            if ($useCache == true) {
                if (!Uri::isAbsolute($url)) {
                    /** @throw AbsoluteUriNeeded when attempting to use a
                     * non-absolute URL as a cache key. */
                    throw new AbsoluteUriNeeded($url);
                }
            } else {
                $useCache = Uri::isAbsolute($url);
            }

            if ($useCache) {
                // normalize URL when used for caching
                $url = (string)UriNormalizer::normalize($url);

                if (isset(self::$cache_[$url])) {
                    $doc = self::$cache_[$url];

                    if (isset($class) && !($doc instanceof $class)) {
                        /** @throw TypeError when cached document is not an
                         *  instance of the expected class. */

                        $exception = new \TypeError(
                            "cached document for $url is " . get_class($doc)
                            . " while expecting $class"
                        );

                        $exception->object = $doc;

                        throw $exception;
                    }

                    return $doc;
                }
            }
        }

        $doc = $class::newFromUrl($url, $libXmlOptions);

        if ($useCache) {
            self::$cache_[$url] = $doc;
        }

        return $doc;
    }

    public function createFromXmlText(
        string $xml,
        ?string $class = null,
        ?int $libXmlOptions = null
    ): Document {
        if (!isset($class)) {
            $class = $this->xmlTextToClass($xml);
        }

        $doc = $class::newFromXmlText($xml, $libXmlOptions);

        return $doc;
    }

    public function urlToClass(string $url): string
    {
        $nsName = ShallowDocument::newFromUrl($url)
            ->documentElement->namespaceURI;

        return static::NS_NAME_TO_CLASS[$nsName] ?? static::DEFAULT_CLASS;
    }

    public function xmlTextToClass(string $xml): string
    {
        $nsName = ShallowDocument::newFromXmlText($xml)
            ->documentElement->namespaceURI;

        return static::NS_NAME_TO_CLASS[$nsName] ?? static::DEFAULT_CLASS;
    }
}
