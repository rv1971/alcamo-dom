<?php

namespace alcamo\dom;

use alcamo\collection\PreventWriteArrayAccessTrait;
use alcamo\exception\{
    ErrorHandler,
    FileLoadFailed,
    SyntaxError,
    Uninitialized
};
use alcamo\dom\xsl\Document as Stylesheet;

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
 * @brief DOM Document class with extra features
 *
 * The ArrayAccess interface provides read access to elements by ID.
 *
 * The IteratorAggregate interface is served with iteration over child
 * elements of the document element.
 *
 * @date Last reviewed 2021-07-01
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
    use PreventWriteArrayAccessTrait;

    /// Node classes that will be registered for each document instance
    public const NODE_CLASSES = [
        'DOMAttr'                  => Attr::class,
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
    public const LIBXML_OPTIONS =
        LIBXML_COMPACT | LIBXML_NOBLANKS | LIBXML_NSCLEAN;

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

     /// Default class for a new document factory
    public const DEFAULT_DOCUMENT_FACTOTRY_CLASS = DocumentFactory::class;

    /// XPath to find all \<xsd:documentation> elements
    private const ALL_DOCUMENTATION_XPATH = '//xsd:documentation';

    /**
     * @brief Create a document from a URL
     *
     * @param $url URL to get the data from.
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
    public static function newFromUrl(
        string $url,
        ?DocumentFactoryInterface $documentFactory = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null
    ): self {
        $doc = new static($documentFactory, $loadFlags, $libxmlOptions);

        $doc->loadUrl($url);

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
     * @param $url Document URL
     */
    public static function newFromXmlText(
        string $xmlText,
        ?DocumentFactoryInterface $documentFactory = null,
        ?int $loadFlags = null,
        ?int $libxmlOptions = null,
        ?string $url = null
    ) {
        $doc = new static($documentFactory, $loadFlags, $libxmlOptions);

        $doc->loadXmlText($xmlText, $url);

        return $doc;
    }

    private static $docRegistry_ = []; ///< Used for conserve()

    protected $documentFactory_;      ///< DocumentFactoryInterface
    protected $loadFlags_;            ///< int
    protected $libxmlOptions_;        ///< int

    private $xPath_;                  ///< XPath
    private $xsltStylesheet_ = false; ///< Document or `null`

    public function __clone()
    {
        /* Each document need sits own XPath objec because the latter contains
         * a reference to the former. */
        $this->xPath_ = null;

        /* All other properties may be shared among documents. */
    }

    /**
     * @brief Construct an empty document
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

        $this->documentFactory_ = $documentFactory;

        $this->loadFlags_ = $loadFlags
            ?? (
                isset($documentFactory)
                    ? $documentFactory->getLoadFlags()
                    : null
            )
            ?? static::LOAD_FLAGS;

        $this->libxmlOptions_ = $libxmlOptions
            ?? (
                isset($documentFactory)
                    ? $documentFactory->getLibxmlOptions()
                    : null
            )
            ?? static::LIBXML_OPTIONS;

        /** Register @ref NODE_CLASSES. */
        foreach (static::NODE_CLASSES as $baseClass => $extendedClass) {
            $this->registerNodeClass($baseClass, $extendedClass);
        }
    }

    /// Return a new instance of DocumentFactory
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        if (!isset($this->documentFactory_)) {
            $this->documentFactory_ = $this->createDocumentFactory();
        }

        return $this->documentFactory_;
    }

    /**
     * @brief Load a URL into a document
     *
     * @param $url URL to get the data from
     */
    public function loadUrl(string $url): void
    {
        $handler = new ErrorHandler();

        try {
            if (!$this->load($url, $this->libxmlOptions_)) {
                /** @throw alcamo::exception::FileLoadFailed if
                 *  [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
                 *  fails. */
                throw (new FileLoadFailed())
                    ->setMessageContext([ 'filename' => $url ]);
            }
        } catch (\ErrorException $e) {
            /** @throw alcamo::exception::FileLoadFailed if any libxml warning
             *  or error occurs. */
            throw FileLoadFailed::newFromPrevious($e, [ 'filename' => $url ]);
        }

        /** Ensure that the file:// protocol is preserved in the
         *  `documentURI` property. */
        if (substr($url, 0, 5) == 'file:' && $this->documentURI[0] == '/') {
            $this->documentURI = "file://$this->documentURI";
        }

        /** After loading, run the afterLoad() hook. */
        $this->afterLoad();
    }

    /**
     * @brief Load XML text into a document
     *
     * @param $xmlText XML text
     *
     * @param $url Document URL
     */
    public function loadXmlText(string $xmlText, ?string $url = null): void
    {
        $handler = new ErrorHandler();

        try {
            if (
                !$this->loadXML($xmlText, $this->libxmlOptions_)
            ) {
                /** @throw alcamo::exception::SyntaxError if
                 *  [DOMDocument::loadXML()](https://www.php.net/manual/en/domdocument.loadxml)
                 *  fails. */
                throw (new SyntaxError())
                    ->setMessageContext([ 'inData' => $xmlText ]);
            }
        } catch (\ErrorException $e) {
            /** @throw alcamo::exception::SyntaxError if any libxml warning or
             *  error occurs. */
            throw SyntaxError::newFromPrevious($e, [ 'inData' => $xmlText ]);
        }

        if (isset($url)) {
            $this->documentURI = $url;
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

    /// Call Element::getIterator() on document element
    public function getIterator(): \Traversable
    {
        return $this->documentElement->getIterator();
    }

    /// Readonly ArrayAccess access to elements by ID
    public function offsetExists($id): bool
    {
        return $this->getElementById($id) !== null;
    }

    /// Readonly ArrayAccess access to elements by ID
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

    /// Run DOMXPath::query() relative to root node
    public function query(string $expr)
    {
        return $this->getXPath()->query($expr);
    }

    /// Run DOMXPath::evaluate() relative to root node
    public function evaluate(string $expr)
    {
        return $this->getXPath()->evaluate($expr);
    }

    /**
     * @brief XSLT stylesheet based on the first xml-stylesheet processing
     * instruction, if any
     */
    public function getXsltStylesheet(): ?Document
    {
        if ($this->xsltStylesheet_ === false) {
            if (!isset($this->documentElement)) {
                /** @throw alcamo::exception::Uninitialized if called on an
                 *  empty document. */
                throw new Uninitialized();
            }

            $pi = $this->query('/processing-instruction("xml-stylesheet")')[0];

            if (!isset($pi) || $pi->type != 'text/xsl') {
                return $this->xsltStylesheet_ = null;
            }

            if (
                !$this->xsltStylesheet_ = $this->getDocumentFactory()
                    ->createFromUrl($pi->href, Stylesheet::class)
            ) {
                /** @throw alcamo::exception::FileLoadFailed if a stylesheet
                 *  is specified but cannot be loaded. */
                throw new FileLoadFailed($pi->href);
            }
        }

        return $this->xsltStylesheet_;
    }

    /// Reparse - useful to get line numbers right after changes
    public function reparse(): self
    {
        $url = $this->documentURI;

        $this->formatOutput = true;

        $this->loadXml($this->saveXML(), $this->libxmlOptions_);

        $this->documentURI = $url;

        unset($this->xPath_);

        return $this;
    }

    /**
     * @brief Remove all \<xsd:documentation> elements
     *
     * @param ?bool $doReparse Whether to call reparse() afterwards
     *
     * Also remove any \<xsd:annotation> elements that have become empty this
     * way.
     */
    public function stripXsdDocumentation(?bool $doReparse = null): self
    {
        foreach ($this->query(self::ALL_DOCUMENTATION_XPATH) as $xsdElement) {
            $parent = $xsdElement->parentNode;

            $parent->removeChild($xsdElement);

            if (
                !isset($parent->firstChild)
                && $parent->namespaceURI == self::XSD_NS
                && $parent->localName == 'annotation'
            ) {
                $parent->parentNode->removeChild($parent);
            }
        }

        return $doReparse ? $this->reparse() : $this;
    }

    /// Return a new instance of DocumentFactory
    protected function createDocumentFactory(): DocumentFactoryInterface
    {
        $class = static::DEFAULT_DOCUMENT_FACTOTRY_CLASS;

        return new $class(
            $this->baseURI,
            $this->loadFlags_,
            $this->libxmlOptions_
        );
    }

    /// Perform any initialization to be done after document loading
    protected function afterLoad(): void
    {
        /** Unset any properties that might refer to a preceding document
         * content. */
        $this->xPath_ = null;
        $this->xsltStylesheet_ = false;
        $this->schemaLocations_ = null;

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
            $this->reparse();
        }
    }
}
