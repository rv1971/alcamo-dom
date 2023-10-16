<?php

namespace alcamo\dom;

use alcamo\collection\PreventWriteArrayAccessTrait;
use alcamo\exception\{
    DataValidationFailed,
    ErrorHandler,
    FileLoadFailed,
    SyntaxError,
    Uninitialized
};

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
 * @brief DOM Document class having factory methods with validation
 *
 * The ArrayAccess interface provides read access to elements by ID.
 *
 * @date Last reviewed 2021-07-01
 */
class Document extends \DOMDocument implements
    \ArrayAccess,
    BaseUriInterface,
    HasDocumentFactoryInterface,
    \IteratorAggregate
{
    use PreventWriteArrayAccessTrait;
    use BaseUriTrait;

    /// Namespace mappings that will be registered for each document instance
    public const NSS = [
        'dc'    => 'http://purl.org/dc/terms/',
        'owl'   => 'http://www.w3.org/2002/07/owl#',
        'rdf'   => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs'  => 'http://www.w3.org/2000/01/rdf-schema#',
        'self'  => 'https://github.com/rv1971/alcamo-dom/',
        'xh'    => 'http://www.w3.org/1999/xhtml',
        'xh11d' => 'http://www.w3.org/1999/xhtml/datatypes/',
        'xml'   => 'http://www.w3.org/XML/1998/namespace',
        'xsd'   => 'http://www.w3.org/2001/XMLSchema',
        'xsi'   => 'http://www.w3.org/2001/XMLSchema-instance',
    ];

    /// Dublin core namespace
    public const DC_NS = self::NSS['dc'];

    /// OWL Web Ontology Language namespace
    public const OWL_NS = self::NSS['owl'];

    /// XML namespace
    public const XML_NS = self::NSS['xml'];

    /// XML Schema namespace
    public const XSD_NS = self::NSS['xsd'];

    /// XML Schema instance namespace
    public const XSI_NS = self::NSS['xsi'];

    /// Node classes that will be registered for each document instance
    public const NODE_CLASSES = [
        'DOMAttr'    => Attr::class,
        'DOMElement' => Element::class,
        'DOMText'    => Text::class
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

    /// Pretty-format and re-parse to get reasonable line numbers
    public const FORMAT_AND_REPARSE = 8;

    /// OR-Combination of the above constants
    public const LOAD_FLAGS = 0;

    /**
     * @brief Create a document from a URL
     *
     * @param $url URL to get the data from.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public static function newFromUrl(
        string $url,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): self {
        $doc = new static();

        $doc->loadUrl($url, $libXmlOptions, $loadFlags);

        /** Ensure that the file:// protocol is preserved in the
         *  `documentURI` property. */
        if (substr($url, 0, 5) == 'file:' && $doc->documentURI[0] == '/') {
            $doc->documentURI = "file://$doc->documentURI";
        }

        return $doc;
    }

    /**
     * @brief Create a document from XML text
     *
     * @param $xml XML text
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public static function newFromXmlText(
        string $xml,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null,
        ?string $url = null
    ) {
        $doc = new static();

        $doc->loadXmlText($xml, $libXmlOptions, $loadFlags, $url);

        return $doc;
    }

    private static $docRegistry_ = []; ///< Used for conserve()

    private $xPath_;                 ///< XPath
    private $xsltProcessor_ = false; ///< XSLTProcessor or `null`
    private $schemaLocations_;       ///< Array of schema locations

    /// @sa See [DOMDocument::__construct()](https://www.php.net/manual/en/domdocument.construct)
    public function __construct($version = null, $encoding = null)
    {
        parent::__construct($version, $encoding);

        /** Register @ref NODE_CLASSES. */
        foreach (static::NODE_CLASSES as $baseClass => $extendedClass) {
            $this->registerNodeClass($baseClass, $extendedClass);
        }
    }

    /// Return a new instance of DocumentFactory
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    /**
     * @brief Load a URL into a document
     *
     * @param $url URL to get the data from.
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public function loadUrl(
        string $url,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): void {
        $handler = new ErrorHandler();

        try {
            if (!$this->load($url, $libXmlOptions ?? static::LIBXML_OPTIONS)) {
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

        /** After loading, run the afterLoad() hook. */
        $this->afterLoad($libXmlOptions, $loadFlags);
    }

    /**
     * @brief Load XML text into a document
     *
     * @param $xml XML text
     *
     * @param $libXmlOptions See $options in
     * [DOMDocument::load()](https://www.php.net/manual/en/domdocument.load)
     */
    public function loadXmlText(
        string $xml,
        ?int $libXmlOptions = null,
        ?int $loadFlags = null,
        ?string $url = null
    ): void {
        $handler = new ErrorHandler();

        try {
            if (
                !$this->loadXML($xml, $libXmlOptions ?? static::LIBXML_OPTIONS)
            ) {
                /** @throw alcamo::exception::SyntaxError if
                 *  [DOMDocument::loadXML()](https://www.php.net/manual/en/domdocument.loadxml)
                 *  fails. */
                throw (new SyntaxError())
                    ->setMessageContext([ 'inData' => $xml ]);
            }
        } catch (\ErrorException $e) {
            /** @throw alcamo::exception::SyntaxError if any libxml warning or
             *  error occurs. */
            throw SyntaxError::newFromPrevious($e, [ 'inData' => $xml ]);
        }

        if (isset($url)) {
            $this->documentURI = $url;
        }

        /** After loading, run the afterLoad() hook. */
        $this->afterLoad($libXmlOptions, $loadFlags);
    }

    /**
     * @brief Ensure there is always a reference to the complete object
     *
     * Thus, it remains available through the `$ownerDocument` property of its
     * nodes. Without this, when no PHP variable references the document
     * object, the `$ownerDocument` property returns the bare DOMDocument
     * object, forgetting any properties added in derived classes.
     */
    public function conserve(): self
    {
        return (self::$docRegistry_[spl_object_hash($this)] = $this);
    }

    /// Undo the effect of conserve(), allowing the object to be destroyed
    public function unconserve()
    {
        unset(self::$docRegistry_[spl_object_hash($this)]);
    }

    /// Call Element::getIterator() on document element
    public function getIterator()
    {
        return $this->documentElement->getIterator();
    }

    /// Readonly ArrayAccess access to elements by ID
    public function offsetExists($id)
    {
        return $this->getElementById($id) !== null;
    }

    /// Readonly ArrayAccess access to elements by ID
    public function offsetGet($id)
    {
        return $this->getElementById($id);
    }

    /// Get an XPath object cached in this document
    public function getXPath(): XPath
    {
        if (!isset($this->xPath_)) {
            if (!$this->documentElement) {
                /** @throw alcamo::exception::Uninitialized if called on an
                 *  empty document. */
                throw new Uninitialized();
            }

            $this->xPath_ = new XPath($this);

            /** All namespaces in @ref NSS are registered in the XPath
             *  object. */
            foreach (static::NSS as $prefix => $uri) {
                $this->xPath_->registerNamespace($prefix, $uri);
            }
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
     * @brief Get pseudo attributes of the first processing instruction for
     * the given target
     *
     * @return SimpleXMLElement with the pseudo attributes as attributes, or
     * `null` if ther is no instruction for that target.
     */
    public function getFirstPiPseudoAttrs(string $piTarget): ?\SimpleXMLElement
    {
        $firstPi = $this->query("/processing-instruction('$piTarget')")[0];

        return isset($firstPi)
            ? simplexml_load_string("<x {$firstPi->nodeValue}/>")
            : null;
    }

    /**
     * @brief XSLT processor based on the first xml-stylesheet processing
     * instruction, if any
     */
    public function getXsltProcessor(): ?\XSLTProcessor
    {
        if ($this->xsltProcessor_ === false) {
            if (!$this->documentElement) {
                /** @throw alcamo::exception::Uninitialized if called on an
                 *  empty document. */
                throw new Uninitialized();
            }

            $pseudoAttrs = $this->getFirstPiPseudoAttrs('xml-stylesheet');

            if (!isset($pseudoAttrs) || $pseudoAttrs['type'] != 'text/xsl') {
                $this->xsltProcessor_ = null;
                return null;
            }

            $this->xsltProcessor_ = new \XSLTProcessor();

            $xslUrl = $this->resolveUri($pseudoAttrs['href']);

            if (
                !$this->xsltProcessor_->importStylesheet(
                    Document::newFromUrl($xslUrl)
                )
            ) {
                /** @throw alcamo::exception::FileLoadFailed if a stylesheet
                 *  is specified but cannot be loaded. */
                throw new FileLoadFailed($xslUrl);
            }
        }

        return $this->xsltProcessor_;
    }

    /**
     * @brief Array of absolute Uri objects indexed by namespace.
     *
     * Empty if there is no `schemaLocation` attribute.
     */
    public function getSchemaLocations(): array
    {
        if (!isset($this->schemaLocations_)) {
            if (
                $this->documentElement
                    ->hasAttributeNS(self::XSI_NS, 'schemaLocation')
            ) {
                $items = preg_split(
                    '/\s+/',
                    $this->documentElement
                        ->getAttributeNS(self::XSI_NS, 'schemaLocation')
                );

                $this->schemaLocations_ = [];

                for ($i = 0; isset($items[$i]); $i += 2) {
                    $this->schemaLocations_[$items[$i]] =
                        $this->resolveUri($items[$i + 1]);
                }
            } else {
                $this->schemaLocations_ = [];
            }
        }

        return $this->schemaLocations_;
    }

    /**
     * @brief Validate against an XSD document supplied as a URL
     *
     * @param $url URL of the XSD.
     *
     * @param $libXmlOptions See $flags in
     * [DOMDocument::schemaValidate()](https://www.php.net/manual/en/domdocument.schemavalidate)
     *
     * @throw alcamo::exception::DataValidationFailed when encountering
     *  validation errors.
     */
    public function validateAgainstXsd(
        string $schemaUrl,
        ?int $libXmlOptions = null
    ): self {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$this->schemaValidate($schemaUrl, $libXmlOptions)) {
            $this->processLibxmlErrors();
        }

        return $this;
    }

    /**
     * @brief Validate against an XSD document supplied as text
     *
     * @param $xsdText Source text of the XSD.
     *
     * @param $libXmlOptions See $flags in
     * [DOMDocument::schemaValidate()](https://www.php.net/manual/en/domdocument.schemavalidate)
     *
     * @throw alcamo::exception::DataValidationFailed when encountering
     *  validation errors.
     */
    public function validateAgainstXsdText(
        string $xsdText,
        ?int $libXmlOptions = null
    ): self {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            if (!$this->schemaValidateSource($xsdText, $libXmlOptions)) {
                $this->processLibxmlErrors();
            }
        } catch (\Throwable $e) {
            throw DataValidationFailed::newFromPrevious(
                $e,
                [
                    'inData' => $this->saveXML(),
                    'atUri' => $this->documentURI
                ]
            );
        }

        return $this;
    }

    /**
     * @brief Validate with schemas given in xsi:schemaLocation or
     * xsi:noNamespaceSchemaLocation.
     *
     * @param $libXmlOptions See $flags in
     * [DOMDocument::schemaValidate()](https://www.php.net/manual/en/domdocument.schemavalidate)
     *
     * Silently do nothing if none of the two is present.
     */
    public function validate(?int $libXmlOptions = null): self
    {
        if (
            $this->documentElement
                ->hasAttributeNS(self::XSI_NS, 'noNamespaceSchemaLocation')
        ) {
            return $this->validateAgainstXsd(
                $this->resolveUri(
                    $this->documentElement->getAttributeNS(
                        self::XSI_NS,
                        'noNamespaceSchemaLocation'
                    )
                ),
                $libXmlOptions
            );
        }

        if (
            !$this->documentElement
                ->hasAttributeNS(self::XSI_NS, 'schemaLocation')
        ) {
            return $this;
        }

        /**
         * In the case of `schemaLocation`, create an XSD importing all
         *  mentioned schemas and validate against it.
         */

        $xsdText =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<schema xmlns="http://www.w3.org/2001/XMLSchema" '
            . 'targetNamespace="' . self::NSS['self'] . 'validate#">';

        foreach ($this->getSchemaLocations() as $ns => $schemaUrl) {
            $xsdText .=
                "<import namespace='$ns' schemaLocation='$schemaUrl'/>";
        }

        $xsdText .= '</schema>';

        return $this->validateAgainstXsdText($xsdText, $libXmlOptions);
    }

    /// Perform any initialization to be done after document loading
    protected function afterLoad(
        ?int $libXmlOptions = null,
        ?int $loadFlags = null
    ): void {
        /** Unset any properties that might refer to a preceding document
         * content. */
        $this->xPath_ = null;
        $this->xsltProcessor_ = false;
        $this->schemaLocations_ = null;

        if (!isset($loadFlags)) {
            $loadFlags = static::LOAD_FLAGS;
        }

        if ($loadFlags & self::VALIDATE_AFTER_LOAD) {
            $this->validate();
        }

        if ($loadFlags & self::XINCLUDE_AFTER_LOAD) {
            $this->xinclude($libXmlOptions ?? static::LIBXML_OPTIONS);

            if ($loadFlags & self::VALIDATE_AFTER_XINCLUDE) {
                $this->validate();
            }
        }

        if ($loadFlags & self::FORMAT_AND_REPARSE) {
            $url = $this->documentURI;

            $this->formatOutput = true;

            $this->loadXml(
                $this->saveXML(),
                $libXmlOptions ?? static::LIBXML_OPTIONS
            );

            $this->documentURI = $url;
        }
    }

    private function processLibxmlErrors(): void
    {
        $messages = [];

        foreach (libxml_get_errors() as $error) {
            /** Suppress warning "namespace was already imported". */
            if (
                strpos($error->message, 'namespace was already imported')
                !== false
            ) {
                continue;
            }

            $messages[] = "$error->file:$error->line $error->message";
        }

        /** @throw alcamo::exception::DataValidationFailed when
         *  encountering validation errors. */
        throw (new DataValidationFailed())->setMessageContext(
            [
                'inData' => $this->saveXML(),
                'atUri' => $this->documentURI,
                'extraMessage' => implode('', $messages)
            ]
        );
    }
}
