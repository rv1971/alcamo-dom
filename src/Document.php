<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\{Uri, UriResolver};
use alcamo\collection\PreventWriteArrayAccessTrait;
use alcamo\exception\{
    AbsoluteUriNeeded,
    DataValidationFailed,
    ErrorHandler,
    FileLoadFailed,
    SyntaxError,
    Uninitialized
};
use alcamo\ietf\UriNormalizer;

/**
 * @brief DOM Document class having factory methods with validation.
 *
 * The ArrayAccess interface provides read access to elements by ID.
 */
class Document extends \DOMDocument implements
    \ArrayAccess,
    HasDocumentFactoryInterface
{
    use PreventWriteArrayAccessTrait;

    public const NS = [
        /// Dublin Core namespace
        'dc' => 'http://purl.org/dc/terms/',

        /// OWL namespace
        'owl' => 'http://www.w3.org/2002/07/owl#',

        /// RDF namespace
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',

        /// RDFS namespace
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',

        /// Namespace for this package
        'self' => 'https://github.com/rv1971/alcamo-dom/',

        /// XHTML namespace
        'xh' => 'http://www.w3.org/1999/xhtml',

        /// XHTML datatypes namespace
        'xh11d' => 'http://www.w3.org/1999/xhtml/datatypes/',

        /// XML Namespace
        'xml' => 'http://www.w3.org/XML/1998/namespace',

        /// XSD Namespace
        'xsd' => 'http://www.w3.org/2001/XMLSchema',

        /// XSI Namespace
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    ];

    public const NODE_CLASS = [
        'DOMAttr'    => Attr::class,
        'DOMElement' => Element::class,
        'DOMText'    => Text::class
    ];

    public const LIBXML_OPTIONS =
        LIBXML_COMPACT | LIBXML_NOBLANKS | LIBXML_NSCLEAN | LIBXML_PEDANTIC;

    public static function newFromUrl(
        string $url,
        ?int $libXmlOptions = null
    ): self {
        $doc = new static();

        $doc->loadUrl($url, $libXmlOptions);

        // ensure the file:// protocol is preserved in the document URI
        if (substr($url, 0, 5) == 'file:' && $doc->documentURI[0] == '/') {
            $doc->documentURI = "file://$doc->documentURI";
        }

        return $doc;
    }

    public static function newFromXmlText(
        string $xml,
        ?int $libXmlOptions = null
    ) {
        $doc = new static();

        $doc->loadXmlText($xml, $libXmlOptions);

        return $doc;
    }

    private static $docRegistry_ = []; ///< Used for conserve()

    private $xPath_;          ///< XPath object.
    private $xsltProcessor_;  ///< XSLTProcessor object or FALSE.
    private $schemaLocations_; ///< Array of schema locations or FALSE.

    public function __construct($version = null, $encoding = null)
    {
        parent::__construct($version, $encoding);

        foreach (static::NODE_CLASS as $baseClass => $extendedClass) {
            $this->registerNodeClass($baseClass, $extendedClass);
        }
    }

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }

    public function loadUrl(string $url, ?int $libXmlOptions = null)
    {
        $handler = new ErrorHandler();

        try {
            if (!$this->load($url, $libXmlOptions ?? static::LIBXML_OPTIONS)) {
                throw new FileLoadFailed($url);
            }
        } catch (\ErrorException $e) {
            throw new FileLoadFailed(
                $url,
                "; {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }

        return $this->afterLoad();
    }

    public function loadXmlText(string $xml, ?int $libXmlOptions = null)
    {
        $handler = new ErrorHandler();

        try {
            if (
                !$this->loadXML($xml, $libXmlOptions ?? static::LIBXML_OPTIONS)
            ) {
                throw new SyntaxError($xml);
            }
        } catch (\ErrorException $e) {
            throw new SyntaxError(
                $xml,
                null,
                "; {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }

        return $this->afterLoad();
    }

    /** Ensure there is always a reference to the complete object so that it
     *  remains available through the `$ownerDocument` property of its
     *  nodes. */
    public function conserve(): self
    {
        return (self::$docRegistry_[spl_object_hash($this)] = $this);
    }

    /** Allow the object to be destroyed. */
    public function unconserve()
    {
        unset(self::$docRegistry_[spl_object_hash($this)]);
    }

    public function offsetExists($id)
    {
        return $this->getElementById($id) !== null;
    }

    public function offsetGet($id)
    {
        return $this->getElementById($id);
    }

    public function getXPath(): XPath
    {
        if (!isset($this->xPath_)) {
            if (!$this->documentElement) {
                /** @throw Uninitialized if called on an empty document. */
                throw new Uninitialized($this);
            }

            $this->xPath_ = new XPath($this);

            foreach (static::NS as $prefix => $uri) {
                $this->xPath_->registerNamespace($prefix, $uri);
            }
        }

        return $this->xPath_;
    }

    /// Run XPath query relative to root node.
    public function query(string $expr)
    {
        return $this->getXPath()->query($expr);
    }

    /// Run and evaluate XPath query relative to root node.
    public function evaluate(string $expr)
    {
        return $this->getXPath()->evaluate($expr);
    }

    /**
     * @brief XSLT processor based on the first xml-stylesheet processing
     * instruction, if any
     */
    public function getXsltProcessor()
    {
        if (!isset($this->xsltProcessor_)) {
            if (!$this->documentElement) {
                /** @throw Uninitialized if called on an empty document. */
                throw new Uninitialized($this);
            }

            $pi = $this->query('/processing-instruction("xml-stylesheet")')[0];

            if (!isset($pi)) {
                return ($this->xsltProcessor_ = false);
            }

            $pseudoAttrs = simplexml_load_string("<x {$pi->nodeValue}/>");

            if ($pseudoAttrs['type'] != 'text/xsl') {
                return ($this->xsltProcessor_ = false);
            }

            $this->xsltProcessor_ = new \XSLTProcessor();

            $xslUrl = UriResolver::resolve(
                new Uri($this->documentURI),
                new Uri($pseudoAttrs['href'])
            );

            if (
                !$this->xsltProcessor_->importStylesheet(
                    self::newFromUrl($xslUrl)
                )
            ) {
                throw new FileLoadFailed($xslUrl);
            }
        }

        return $this->xsltProcessor_;
    }
    /**
     * @return Array of absolute Uri objects indexed by namespace. Empty if
     * there is no `schemaLocation` attribute.
     */
    public function getSchemaLocations(): array
    {
        if (!isset($this->schemaLocations_)) {
            $baseUri = new Uri($this->documentURI);

            if (
                $this->documentElement->hasAttributeNS(
                    self::NS['xsi'],
                    'schemaLocation'
                )
            ) {
                $items = preg_split(
                    '/\s+/',
                    $this->documentElement->getAttributeNS(
                        self::NS['xsi'],
                        'schemaLocation'
                    )
                );

                $this->schemaLocations_ = [];

                for ($i = 0; isset($items[$i]); $i += 2) {
                    $this->schemaLocations_[$items[$i]] = UriResolver::resolve(
                        $baseUri,
                        new Uri($items[$i + 1])
                    );
                }
            } else {
                $this->schemaLocations_ = [];
            }
        }

        return $this->schemaLocations_;
    }

    /// Validate with given XML Schema.
    public function validateWithSchema(
        string $schemaUrl,
        ?int $libXmlOptions = null
    ): self {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$this->schemaValidate($schemaUrl, $libXmlOptions)) {
            $messages = [];

            foreach (libxml_get_errors() as $error) {
                /* Suppress warnings. */
                if (
                    strpos($error->message, 'namespace was already imported')
                     !== false
                ) {
                    continue;
                }

                $messages[] = "$error->file:$error->line $error->message";
            }

            throw new DataValidationFailed(
                $this->saveXML(),
                $this->documentURI,
                '; ' . implode('', $messages)
            );
        }

        return $this;
    }

    /// Validate with given XML Schema source.
    public function validateWithSchemaSource(
        string $schemaSource,
        ?int $libXmlOptions = null
    ): self {
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (!$this->schemaValidateSource($schemaSource, $libXmlOptions)) {
            $messages = [];

            foreach (libxml_get_errors() as $error) {
                /* Suppress warnings. */
                if (
                    strpos($error->message, 'namespace was already imported')
                     !== false
                ) {
                    continue;
                }

                $messages[] = "$error->file:$error->line $error->message";
            }

            throw new DataValidationFailed(
                $this->saveXML(),
                $this->documentURI,
                '; ' . implode('', $messages)
            );
        }

        return $this;
    }

    /**
     * @brief Validate with schemas given in xsi:schemaLocation or
     * xsi:noNamespaceSchemaLocation.
     *
     * Silently do nothing if none of the two is present.
     */
    public function validate(?int $libXmlOptions = null): self
    {
        $baseUri = new Uri($this->documentURI);

        if (
            $this->documentElement->hasAttributeNS(
                self::NS['xsi'],
                'noNamespaceSchemaLocation'
            )
        ) {
            return $this->validateWithSchema(
                UriResolver::resolve(
                    $baseUri,
                    new Uri(
                        $this->documentElement->getAttributeNS(
                            self::NS['xsi'],
                            'noNamespaceSchemaLocation'
                        )
                    )
                ),
                $libXmlOptions
            );
        }

        if (
            !$this->documentElement->hasAttributeNS(
                self::NS['xsi'],
                'schemaLocation'
            )
        ) {
            return $this;
        }

        $schemaSource =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<schema xmlns="http://www.w3.org/2001/XMLSchema" '
            . 'targetNamespace="' . self::NS['self'] . 'validate">';

        foreach ($this->getSchemaLocations() as $ns => $schemaUrl) {
            $schemaSource .=
                "<import namespace='$ns' schemaLocation='$schemaUrl'/>";
        }

        $schemaSource .= '</schema>';

        return $this->validateWithSchemaSource($schemaSource, $libXmlOptions);
    }

    // Any initialization to be done after document loading
    protected function afterLoad()
    {
    }
}
