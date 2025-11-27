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
    /// Map of `dc:identifier` prefixes to PHP classes for DOM documents
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

    private $baseUri_;       ///< ?UriInterface
    private $loadFlags_;     ///< ?int
    private $libxmlOptions_; ///< ?int

    /**
     * @param $baseUri string|UriInterface Base URI to locate documents
     *
     * @param $loadFlags OR-Combination of the load class constants in the
     * alcamo::dom::Document class.
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public function __construct(
        $baseUri = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ) {
        if (isset($baseUri)) {
            $this->baseUri_ = $baseUri instanceof UriInterface
                ? $baseUri
                : new Uri($baseUri);
        }

        $this->loadFlags_ = $loadFlags;
        $this->libxmlOptions_ = $libxmlOptions;

        $this->dcIdentifierPrefixToClass_ = new PrefixFirstMatchCollection(
            static::DC_IDENTIFIER_PREFIX_TO_CLASS
        );
    }

    /// Return the base URI used to resolve relative URIs
    public function getBaseUri(): ?UriInterface
    {
        return $this->baseUri_;
    }

    public function resolveUri($uri): ?UriInterface
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if ($uri->getScheme() !== '') {
            return $uri;
        }

        return isset($this->baseUri_)
            ? UriResolver::resolve($this->baseUri_, $uri)
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

    public function createFromUri(
        $uri,
        ?string $class = null,
        ?bool $useCache = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ): ?\DOMNode {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        if (isset($this->baseUri_)) {
            $uri = UriResolver::resolve($this->baseUri_, $uri);
        }

        $docUri = $uri->withFragment('');
        $fragment = $uri->getFragment();

        if ($useCache !== false) {
            if ($useCache === true) {
                if (!Uri::isAbsolute($docUri)) {
                    /** @throw alcamo::exception::AbsoluteUriNeeded when
                     * attempting to use the cache for a document with a
                     * non-absolute URI. */
                    throw (new AbsoluteUriNeeded())
                        ->setMessageContext([ 'uri' => $docUri ]);
                }
            } else {
                $useCache = Uri::isAbsolute($docUri);
            }

            if ($useCache) {
                // normalize URI when used for caching
                $docUri = (string)UriNormalizer::normalize($docUri);

                if (isset(DocumentCache::getInstance()[$docUri])) {
                    $doc = DocumentCache::getInstance()[$docUri];

                    if (isset($class) && !($doc instanceof $class)) {
                        /** @throw alcamo::exception::InvalidType when cached
                         *  document is not an instance of the
                         *  requested class. */
                        throw (new InvalidType())->setMessageContext(
                            [
                                'value' => $doc,
                                'expectedOneOf' => [ $class ],
                                'atUri' => $docUri
                            ]
                        );
                    }

                    return $fragment === '' ? $doc : $doc[$fragment];
                }
            }
        }

        if (!isset($class)) {
            $class = $this
                ->getClassForDocument(ShallowDocument::newFromUri($docUri));
        }

        /** If the document is not taken from the cache, call the newFromUri()
         *  method of the document class to create a new instance. */
        $doc = $class::newFromUri(
            $docUri,
            $this,
            $loadFlags,
            $libxmlOptions ?? $this->libxmlOptions_
        );

        if ($useCache) {
            DocumentCache::getInstance()->add($doc);
        }

        return $fragment === '' ? $doc : $doc[$fragment];
    }

    /**
     * @brief Create a document from XML text
     *
     * @param $xmlText XML text
     *
     * @param $class PHP class to use for the new document. If `null`,
     * xmlTextToClass() is called to get the class.
     *
     * @param $loadFlags OR-Combination of the load constants in the
     * alcamo::dom::Document class. If not given, getLoadFlags() is used.
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load). If
     * not given, getLibxmlOptions() is used.
     *
     * @param $uri Document URI
     */
    public function createFromXmlText(
        string $xmlText,
        ?string $class = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null,
        ?string $uri = null
    ): Document {
        if (!isset($class)) {
            $class = $this->getClassForDocument(
                ShallowDocument::newFromXmlText($xmlText)
            );
        }

        if (isset($uri)) {
            if (isset($this->baseUri_)) {
                if (!($uri instanceof UriInterface)) {
                    $uri = new Uri($uri);
                }

                $uri = UriResolver::resolve($this->baseUri_, $uri);
            }
        }

        $doc = $class::newFromXmlText(
            $xmlText,
            $this,
            $loadFlags,
            $libxmlOptions ?? $this->libxmlOptions_,
            $uri
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
         * - If there is a `dc:identifier` attribute in the document element
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
