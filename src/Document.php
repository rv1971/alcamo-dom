<?php

namespace alcamo\dom;

use alcamo\collection\PreventWriteArrayAccessTrait;
use alcamo\exception\{
    ErrorHandler,
    FileLoadFailed,
    SyntaxError,
    Uninitialized
};
use alcamo\rdfa\{LiteralFactory, RdfaFactory};
use alcamo\xml\{NamespaceConstantsInterface, NamespaceMapsInterface};

/**
 * @namespace alcamo::dom
 *
 * @brief Convenience features for DOM nodes
 *
 * The derived classes for attribute and element nodes defined in
 * this namespace do not add properties, only methods, hence no special
 * measures are necessary to conserve nodes.
 */

/**
 * @brief DOM Document class with additional interfaces and features
 *
 * The ArrayAccess interface provides read access to elements by ID.
 *
 * The IteratorAggregate interface provides iteration over child elements of
 * the document element.
 *
 * @date Last reviewed 2025-10-26
 */
class Document extends \DOMDocument implements
    \ArrayAccess,
    HavingBaseUriInterface,
    HavingDocumentFactoryInterface,
    \IteratorAggregate,
    NamespaceConstantsInterface,
    XPathQueryableInterface
{
    use HavingBaseUriTrait;
    use HavingDocumentFactoryTrait;
    use PreventWriteArrayAccessTrait;

    /// Map of canonical namespace prefixes
    public const NS_PRFIX_TO_NS_NAME =
        NamespaceMapsInterface::NS_PRFIX_TO_NS_NAME;

    /// Default class for a new document factory
    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;

    /// Node classes that will be registered for each document instance
    public const NODE_CLASSES = [
        'DOMAttr'                  => Attr::class,
        'DOMComment'               => Comment::class,
        'DOMElement'               => Element::class,
        'DOMProcessingInstruction' => ProcessingInstruction::class,
        'DOMText'                  => Text::class
    ];

    /**
     * @brief Default libxml options when loading a document
     *
     * The option `LIBXML_PEDANTIC` has been removed because it makes the
     * parser fail when reading `xml:lang` values containing private
     * subtags. This is a known but unresolved bug in the underlying libxml2,
     * see [xmllint fails to validate
     * xs:language](https://stackoverflow.com/questions/29314958/xmllint-fails-to-validate-xslanguage).
     */
    public const LIBXML_OPTIONS = LIBXML_COMPACT | LIBXML_NOBLANKS;

    /// Validate document just after load (before xinclude(), if requested)
    public const VALIDATE_AFTER_LOAD = 1;

    /// Call xinclude() after load (and after validation, if requested)
    public const XINCLUDE_AFTER_LOAD = 2;

    /// Validate document after xinclude()
    public const VALIDATE_AFTER_XINCLUDE = 4;

    /**
     * @brief Pretty-format and re-parse the document
     *
     * This is useful to get reasonable line numbers after xinclude() because
     * otherwise the included nodes keep their line numbers.
     */
    public const FORMAT_AND_REPARSE = 8;

    /// OR-Combination of the above constants
    public const LOAD_FLAGS = 0;

    /// Factory class used to create RDF literal objects
    public const LITERAL_FACTORY_CLASS = LiteralFactory::class;

    /// Factory class used to create RDFa data
    public const RDFA_FACTORY_CLASS = RdfaFactory::class;

    /**
     * @brief Create a document from a URI
     *
     * @param $uri URI to get the data from.
     *
     * @param $documentFactory Document factory to use to create dependent
     * documents, e.g. from links. If not specified, the Document factory will
     * be created by createDocumentFactory() when needed.
     *
     * @param $loadFlags OR-Combination of the above load constants
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public static function newFromUri(
        string $uri,
        ?DocumentFactoryInterface $documentFactory = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ): self {
        $doc = new static($documentFactory, $loadFlags, $libxmlOptions);

        $doc->loadUri($uri);

        return $doc;
    }

    /**
     * @brief Create a document from XML text
     *
     * @param $xmlText XML text
     *
     * @param $documentFactory Document factory to use to create dependent
     * documents, e.g. from links. If not specified, the Document factory will
     * be created by createDocumentFactory() when needed.
     *
     * @param $loadFlags OR-Combination of the above load constants
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     *
     * @param $uri Document URI
     */
    public static function newFromXmlText(
        string $xmlText,
        ?DocumentFactoryInterface $documentFactory = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null,
        ?string $uri = null
    ) {
        $doc = new static($documentFactory, $loadFlags, $libxmlOptions);

        $doc->loadXmlText($xmlText, $uri);

        return $doc;
    }

    private static $docRegistry_ = []; ///< Used for conserve()

    protected $loadFlags_;            ///< int
    protected $libxmlOptions_;        ///< int

    private $xPath_;                  ///< XPath
    private $xsltStylesheet_ = false; ///< Document or `null`

    /// Call clearCache()
    public function __clone()
    {
        $this->clearCache();
    }

    /**
     * @brief Construct an empty document
     *
     * @param $documentFactory Document factory to use to create dependent
     * documents, e.g. from links. If not specified, the document factory will
     * be created as an instance of DEFAULT_DOCUMENT_FACTORY_CLASS.
     *
     * @param $loadFlags OR-Combination of the above load constants
     *
     * @param $libxmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     *
     * @param $version The version number of the document as part of the XML
     * declaration.
     *
     * @param $encoding The encoding of the document as part of the XML
     * declaration.
     */
    public function __construct(
        ?DocumentFactoryInterface $documentFactory = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null,
        $version = null,
        $encoding = null
    ) {
        parent::__construct($version, $encoding);

        $this->loadFlags_ = $loadFlags ?? static::LOAD_FLAGS;

        $this->libxmlOptions_ = $libxmlOptions ?? static::LIBXML_OPTIONS;

        if (isset($documentFactory)) {
            $this->documentFactory_ = $documentFactory;
        } else {
            $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $this->documentFactory_ = new $class(
                $this->baseURI,
                $this->loadFlags_,
                $this->libxmlOptions_
            );
        }

        foreach (static::NODE_CLASSES as $baseClass => $extendedClass) {
            $this->registerNodeClass($baseClass, $extendedClass);
        }
    }

    public function getLibxmlOptions(): ?int
    {
        return $this->libxmlOptions_;
    }

    /**
     * @brief Load a URI into this document
     *
     * @param $uri URI to get the data from
     */
    public function loadUri(string $uri): void
    {
        $handler = new ErrorHandler();

        try {
            libxml_use_internal_errors(false);

            $this->load($uri, $this->libxmlOptions_);
        } catch (\ErrorException $e) {
            /** @throw alcamo::exception::FileLoadFailed if any libxml warning
             *  or error occurs. */
            throw FileLoadFailed::newFromPrevious($e, [ 'filename' => $uri ]);
        }

        /** After loading, run the afterLoad() hook. */
        $this->afterLoad();
    }

    /**
     * @brief Load XML text into this document
     *
     * @param $xmlText XML text
     *
     * @param $uri Document URI
     */
    public function loadXmlText(string $xmlText, ?string $uri = null): void
    {
        $handler = new ErrorHandler();

        try {
            libxml_use_internal_errors(false);

            $this->loadXML($xmlText, $this->libxmlOptions_);
        } catch (\ErrorException $e) {
            /** @throw alcamo::exception::SyntaxError if any libxml warning or
             *  error occurs. */
            throw SyntaxError::newFromPrevious($e, [ 'inData' => $xmlText ]);
        }

        if (isset($uri)) {
            $this->documentURI = $uri;
        }

        /** After loading, run the afterLoad() hook. */
        $this->afterLoad();
    }

    /**
     * @brief Ensure there is always a reference to the complete object
     *
     * Thus, it remains available through the `$ownerDocument` property of its
     * nodes. Without this, when no PHP variable references the document
     * object, the `$ownerDocument` property returns the bare DOMDocument
     * object, forgetting any properties added in this class and derived
     * classes.
     */
    public function conserve(): self
    {
        return (self::$docRegistry_[spl_object_hash($this)] = $this);
    }

    /// Undo the effect of conserve(), allowing the object to be destroyed
    public function unconserve(): void
    {
        unset(self::$docRegistry_[spl_object_hash($this)]);
    }

    /// Call alcamo::dom::Element::getIterator() on the document element
    public function getIterator(): \Traversable
    {
        return $this->documentElement->getIterator();
    }

    /// Provide readonly ArrayAccess access to elements by ID
    public function offsetExists($id): bool
    {
        return $this->getElementById($id) !== null;
    }

    /// Provide readonly ArrayAccess access to elements by ID
    public function offsetGet($id): ?Element
    {
        return $this->getElementById($id);
    }

    /// Get an XPath object cached in this document
    public function getXPath(): XPath
    {
        if (!isset($this->xPath_)) {
            if (!isset($this->documentElement)) {
                /** @throw alcamo::exception::Uninitialized if called on an
                 *  empty document. */
                throw new Uninitialized();
            }

            $this->xPath_ = new XPath($this);
        }

        return $this->xPath_;
    }

    /// Run DOMXPath::query() relative to the document's root node
    public function query(string $expr)
    {
        return $this->getXPath()->query($expr, $this);
    }

    /// Run DOMXPath::evaluate() relative the document's root node
    public function evaluate(string $expr)
    {
        return $this->getXPath()->evaluate($expr, $this);
    }

    /**
     * @brief Get an XSLT stylesheet based on the first xml-stylesheet
     * processing instruction, if any
     */
    public function getXsltStylesheet(): ?self
    {
        if ($this->xsltStylesheet_ === false) {
            if (!isset($this->documentElement)) {
                /** @throw alcamo::exception::Uninitialized if called on an
                 *  empty document. */
                throw new Uninitialized();
            }

            $pi = $this->query('processing-instruction("xml-stylesheet")')[0];

            if (!isset($pi) || $pi->type != 'text/xsl') {
                return $this->xsltStylesheet_ = null;
            }

            $this->xsltStylesheet_ = $this->getDocumentFactory()
                ->createFromUri($pi->resolveUri($pi->href));
        }

        return $this->xsltStylesheet_;
    }

    /// Factory used to create RDF literal objects
    public function getLiteralFactory(): LiteralFactory
    {
        $class = static::LITERAL_FACTORY_CLASS;

        return new $class();
    }

    /// Factory used to create RDFa data
    public function getRdfaFactory(): RdfaFactory
    {
        $class = static::RDFA_FACTORY_CLASS;

        return new $class();
    }

    /**
     * @brief Unset any properties that might refer to a preceding document
     * content.
     *
     * @note Derived classes that add properties may need to extend this
     * method.
     */
    public function clearCache(): void
    {
        $this->xPath_ = null;
        $this->xsltStylesheet_ = false;
    }

    /// Perform any initialization to be done after document loading
    protected function afterLoad(): void
    {
        $this->clearCache();

        /** Add file:// protocol to the `documentURI` property if it has an
         *  absolute path and no other protocol given. */
        if ($this->documentURI[0] == '/') {
            $this->documentURI = "file://$this->documentURI";
        }

        if ($this->loadFlags_ & self::VALIDATE_AFTER_LOAD) {
            (new DocumentValidator())->validate($this);
        }

        if ($this->loadFlags_ & self::XINCLUDE_AFTER_LOAD) {
            try {
                $this->xinclude($this->libxmlOptions_);
            } catch (\ErrorException $e) {
                /* xinclude() may generate a warning if the file to include is
                 * not found, even when a fallback is defined. So this warning
                 * should be ignored. */
                if (strpos($e->getMessage(), 'warning') === false) {
                    throw $e;
                }
            }

            if ($this->loadFlags_ & self::VALIDATE_AFTER_XINCLUDE) {
                (new DocumentValidator())->validate($this);
            }
        }

        if ($this->loadFlags_ & self::FORMAT_AND_REPARSE) {
            (new DocumentModifier())->reparse($this);
        }
    }
}
