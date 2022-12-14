<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, InvalidType, ReadonlyViolation};
use alcamo\uri\{Uri, UriNormalizer};
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * @brief Factory for DOM documents
 *
 * Features caching as well as automatic determination of the document class.
 *
 * @date Last reviewed 2021-07-01
 */
class DocumentFactory implements DocumentFactoryInterface
{
    /// Map of document element extended names to PHP classes for DOM documents
    public const X_NAME_TO_CLASS = [
    ];

    /// Map of document element namespaces to PHP classes for DOM documents
    public const NS_NAME_TO_CLASS = [
    ];

    /// Default class for new DOM documents
    public const DEFAULT_CLASS = Document::class;

    /// Array mapping absolute URLs to Document objects
    private static $cache_;

    /// Add a document to the cache
    public static function addToCache(Document $doc)
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

        if (isset(self::$cache_[$doc->documentURI])) {
            if (self::$cache_[$doc->documentURI] !== $doc) {
                /** @throw alcamo::exception::Locked when attempting to
                 * replace a cache entry with a different document. */
                throw (new ReadonlyViolation())->setMessageContext(
                    [
                        'object' => self::class . ' cache',
                        'extraMessage' => 'attempt to replace cache entry '
                        . "\"{$doc->documentURI}\" "
                        . 'by a different document'
                    ]
                );
            }
        } else {
            self::$cache_[$doc->documentURI] = $doc;
        }
    }

    private $baseUrl_; ///< ?UriInterface

    /// @param $baseUrl string|UriInterface Base URL to locate documents
    public function __construct($baseUrl = null)
    {
        if (isset($baseUrl)) {
            $this->baseUrl_ = $baseUrl instanceof UriInterface
                ? $baseUrl
                : new Uri($baseUrl);
        }
    }

    public function getBaseUrl(): ?UriInterface
    {
        return $this->baseUrl_;
    }

    /**
     * @brief Create a document from a URL
     *
     * @param $url string|UriInterface URL to get the data from.
     *
     * @param $class PHP class to use for the new document. If `null`,
     * urlToClass() is called to get the class.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     *
     * @param $useCache ?bool
     * - if `true`, use the cache
     * - if `false`, do not use the cache
     * - if `null`, use the cache iff $url is absolute
     */
    public function createFromUrl(
        $url,
        ?string $class = null,
        ?int $libXmlOptions = null,
        ?bool $useCache = null,
        ?int $loadFlags = null
    ): Document {
        if (!($url instanceof UriInterface)) {
            $url = new Uri($url);
        }

        if (isset($this->baseUrl_)) {
            $url = UriResolver::resolve($this->baseUrl_, $url);
        }

        if ($useCache !== false) {
            if ($useCache == true) {
                if (!Uri::isAbsolute($url)) {
                    /** @throw alcamo::exception::AbsoluteUriNeeded when
                     * attempting to cache a document with a non-absolute
                     * URL. */
                    throw (new AbsoluteUriNeeded())
                        ->setMessageContext([ 'uri' => $url ]);
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
                        /** @throw alcamo::exception::InvalidType when cached
                         *  document is not an instance of the
                         *  requested class. */

                        throw (new InvalidType())->setMessageContext(
                            [
                                'value' => $doc,
                                'expectedOneOf' => [ $class ],
                                'atUri' => $url
                            ]
                        );
                    }

                    return $doc;
                }
            }
        }

        if (!isset($class)) {
            $class = $this->urlToClass($url);
        }

        /** If the document is not taken from the cache, call the newFromUrl()
         *  method of the document class to create a new instance. */
        $doc = $class::newFromUrl($url, $libXmlOptions, $loadFlags);

        if ($useCache) {
            self::$cache_[$url] = $doc;
        }

        return $doc;
    }

    /**
     * @brief Create a document from XML text
     *
     * @param $xml XML text
     *
     * @param $class PHP class to use for the new document. If `null`,
     * xmlTextToClass() is called to get the class.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public function createFromXmlText(
        string $xml,
        ?string $class = null,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): Document {
        if (!isset($class)) {
            $class = $this->xmlTextToClass($xml);
        }

        $doc = $class::newFromXmlText($xml, $libXmlOptions, $loadFlags);

        return $doc;
    }

    /// Determine a document class to use from a document URL
    public function urlToClass(string $url): string
    {
        $documentElement = ShallowDocument::newFromUrl($url)->documentElement;

        /**
         * - If @ref X_NAME_TO_CLASS contains an item for the extended name of
         *  the document element, return its value.
         * - Otherwise, if @ref NS_NAME_TO_CLASS contains an item for the
         *  namespace name of the document element, return its value.
         * - Otherwise, return @ref DEFAULT_CLASS.
         */
        return
            static::X_NAME_TO_CLASS[
                "$documentElement->namespaceURI $documentElement->localName"
            ]
            ?? static::NS_NAME_TO_CLASS[$documentElement->namespaceURI]
            ?? static::DEFAULT_CLASS;
    }

    /// Determine a document class to use from XML text
    public function xmlTextToClass(string $xml): string
    {
        $documentElement =
            ShallowDocument::newFromXmlText($xml)->documentElement;

        /**
         * - If @ref X_NAME_TO_CLASS contains an item for the extended name of
         *  the document element, return its value.
         * - Otherwise, if @ref NS_NAME_TO_CLASS contains an item for the
         *  namespace name of the document element, return its value.
         * - Otherwise, return @ref DEFAULT_CLASS.
         */
        return
            static::X_NAME_TO_CLASS[
                "$documentElement->namespaceURI $documentElement->localName"
            ]
            ?? static::NS_NAME_TO_CLASS[$documentElement->namespaceURI]
            ?? static::DEFAULT_CLASS;
    }
}
