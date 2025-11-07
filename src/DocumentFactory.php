<?php

namespace alcamo\dom;

use alcamo\collection\PrefixFirstMatchCollection;
use alcamo\dom\decorated\Document as Xsd;
use alcamo\exception\{AbsoluteUriNeeded, InvalidType};
use alcamo\uri\{Uri, UriNormalizer};
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * @brief Factory for DOM documents
 *
 * Features caching as well as automatic determination of the document class.
 *
 * @date Last reviewed 2025-10-26
 */
class DocumentFactory implements
    DocumentFactoryInterface,
    HavingBaseUriInterface
{
    /// Map of dc:identifier prefixes to PHP classes for DOM documents
    public const DC_IDENTIFIER_PREFIX_TO_CLASS = [
    ];

    /// Map of document element extended names to PHP classes for DOM documents
    public const X_NAME_TO_CLASS = [
        self::XSD_NS . ' schema' => Xsd::class
    ];

    /// Map of document element namespaces to PHP classes for DOM documents
    public const NS_NAME_TO_CLASS = [
    ];

    /// Default class for new DOM documents
    public const DEFAULT_DOCUMENT_CLASS = Document::class;

    /**
     * @brief PrefixFirstMatchCollection created from @ref
     * DC_IDENTIFIER_PREFIX_TO_CLASS
     */
    private $dcIdentifierPrefixToClass_;

    private $baseUrl_;       ///< ?UriInterface
    private $loadFlags_;     ///< ?int
    private $libxmlOptions_; ///< ?int

    /**
     * @param $baseUrl string|UriInterface Base URL to locate documents
     *
     * @param $loadFlags OR-Combination of the load class constants in the
     * Document class.
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public function __construct(
        $baseUrl = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ) {
        if (isset($baseUrl)) {
            $this->baseUrl_ = $baseUrl instanceof UriInterface
                ? $baseUrl
                : new Uri($baseUrl);
        }

        $this->loadFlags_ = $loadFlags;
        $this->libxmlOptions_ = $libxmlOptions;

        $this->dcIdentifierPrefixToClass_ = new PrefixFirstMatchCollection(
            static::DC_IDENTIFIER_PREFIX_TO_CLASS
        );
    }

    public function getBaseUri(): ?UriInterface
    {
        return $this->baseUrl_;
    }

    public function resolveUri($uri): ?UriInterface
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if ($uri->getScheme() !== '') {
            return $uri;
        }

        return isset($this->baseUrl_)
            ? UriResolver::resolve($this->baseUrl_, $uri)
            : null;
    }

    public function getLoadFlags(): ?int
    {
        return $this->loadFlags_;
    }

    public function getLibxmlOptions(): ?int
    {
        return $this->libxmlOptions_;
    }

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
     * Document. If not given, getLoadFlags() is used.
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load). If
     * not given, getLibxmlOptions() is used.
     */
    public function createFromUrl(
        $url,
        ?string $class = null,
        ?bool $useCache = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ): Document {
        if (!($url instanceof UriInterface)) {
            $url = new Uri($url);
        }

        if (isset($this->baseUrl_)) {
            $url = UriResolver::resolve($this->baseUrl_, $url);
        }

        if ($useCache !== false) {
            if ($useCache === true) {
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

                if (isset(DocumentCache::getInstance()[$url])) {
                    $doc = DocumentCache::getInstance()[$url];

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
            $class =
                $this->getClassForDocument(ShallowDocument::newFromUrl($url));
        }

        /** If the document is not taken from the cache, call the newFromUrl()
         *  method of the document class to create a new instance. */
        $doc = $class::newFromUrl($url, $this, $loadFlags, $libxmlOptions);

        if ($useCache) {
            DocumentCache::getInstance()->add($doc);
        }

        return $doc;
    }

    /**
     * @brief Create a document from XML text
     *
     * @param $xmlText XML text
     *
     * @param $class PHP class to use for the new document. If `null`,
     * xmlTextToClass() is called to get the class.
     *
     * @param $loadFlags OR-Combination of the above load constants
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     *
     * @param $url Document URL
     */
    public function createFromXmlText(
        string $xmlText,
        ?string $class = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null,
        ?string $url = null
    ): Document {
        if (!isset($class)) {
            $class = $this->getClassForDocument(
                ShallowDocument::newFromXmlText($xmlText)
            );
        }

        if (isset($url)) {
            if (isset($this->baseUrl_)) {
                if (!($url instanceof UriInterface)) {
                    $url = new Uri($url);
                }

                $url = UriResolver::resolve($this->baseUrl_, $url);
            }
        }

        $doc = $class::newFromXmlText(
            $xmlText,
            $this,
            $loadFlags,
            $libxmlOptions,
            $url
        );

        return $doc;
    }

    /**
     * @brief Determine a document class to use for a document
     *
     * Since this method evaluates the document element only, without looking
     * into its children, this method is typically used with a
     * ShallowDocument.
     */
    protected function getClassForDocument(Document $document): string
    {
        $documentElement = $document->documentElement;

        /**
         * - If there is a dc:identifier attribute in the document element
         *   which matches a prefix in @ref DC_IDENTIFIER_PREFIX_TO_CLASS,
         *   return the corresponding class name.
         * - Otherwise, if @ref X_NAME_TO_CLASS contains an item for the
         *   extended name of the document element, return its value.
         * - Otherwise, if @ref NS_NAME_TO_CLASS contains an item for the
         *   namespace name of the document element, return its value.
         * - Otherwise, return @ref DEFAULT_DOCUMENT_CLASS.
         */

        if ($documentElement->hasAttributeNS(Document::DC_NS, 'identifier')) {
            $class = $this->dcIdentifierPrefixToClass_[
                $documentElement->getAttributeNS(Document::DC_NS, 'identifier')
            ];
        }

        return $class
            ?? static::X_NAME_TO_CLASS[(string)$documentElement->getXName()]
            ?? static::NS_NAME_TO_CLASS[$documentElement->namespaceURI]
            ?? static::DEFAULT_DOCUMENT_CLASS;
    }
}
