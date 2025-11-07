<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    ConverterPool,
    Document,
    DocumentCache,
    DocumentFactoryInterface,
    HavingDocumentFactoryInterface,
    NamespaceConstantsInterface
};
use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\extended\Element as ExtElement;
use alcamo\dom\schema\component\{
    AbstractType,
    Attr,
    AttrGroup,
    AttrInterface,
    ComplexType,
    Element,
    Group,
    Notation,
    PredefinedAnySimpleType,
    PredefinedAttr,
    TypeInterface
};
use alcamo\exception\ExceptionInterface;
use alcamo\uri\{FileUriFactory, Uri, UriNormalizer};
use alcamo\xml\XName;
use GuzzleHttp\Psr7\UriResolver;
use Psr\Http\Message\UriInterface;

/**
 * @namespace alcamo::dom::schema
 *
 * @brief Classes to model an XML Schema
 */

/**
 * @brief Complete XML Schema
 *
 * @warning `\<redefine>` is not supported.
 *
 * @date Last reviewed 2025-11-07
 */
class Schema implements
    HavingDocumentFactoryInterface,
    NamespaceConstantsInterface
{
    /// Predefined XSI attributes
    public const XSI_ATTRS = [ 'nil' => 'boolean', 'type' => 'QName' ];

    /**
     * @brief Construct new schema or get it from cache
     *
     * This method works even if a document has no `xsi:schemaLocation`
     *  attribute, in which case the schema has only the predefined components
     *  in the `xml` and the `xsd` namespaces. */
    public static function newFromDocument(
        Document $doc,
        ?DocumentFactoryInterface $documentFactory = null
    ): self {
        $urls = [];

        $schemaLocation = $doc->documentElement
            ->getAttributeNodeNS(self::XSI_NS, 'schemaLocation');

        if ($schemaLocation) {
            foreach (
                ConverterPool::pairsToMap($schemaLocation) as $nsName => $url
            ) {
                $urls[] = $doc->documentElement->resolveUri(new Uri($url));
            }
        }

        return static::newFromUrls(
            $urls,
            $documentFactory ?? $doc->getDocumentFactory()
        );
    }

    /// Construct new schema or get it from cache
    public static function newFromUrls(
        iterable $urls,
        ?DocumentFactoryInterface $documentFactory = null
    ): self {
        $cacheKey = SchemaCache::getInstance()->createKey($urls);

        $schema = SchemaCache::getInstance()[$cacheKey] ?? null;

        if (!isset($schema)) {
            $xsds = [];

            if (!isset($documentFactory)) {
                $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

                $documentFactory = new $class();
            }

            foreach ($urls as $url) {
                $xsds[] = $documentFactory->createFromUrl($url);
            }

            $schema = new static($xsds, $cacheKey, $documentFactory);

            SchemaCache::getInstance()->add($schema);
        }

        return $schema;
    }

    /// Construct new schema or get it from cache
    public static function newFromXsds(
        array $xsds,
        ?DocumentFactoryInterface $documentFactory = null
    ): self {
        $cacheKey = SchemaCache::getInstance()->createKey($xsds);

        $schema = SchemaCache::getInstance()[$cacheKey] ?? null;

        if (!isset($schema)) {
            $schema = new static($xsds, $cacheKey, $documentFactory);

            SchemaCache::getInstance()->add($schema);
        }

        return $schema;
    }

    // Create type from an URL reference indicating an XSD element by ID
    public static function createTypeFromUrl(
        $url,
        ?DocumentFactoryInterface $documentFactory = null
    ): TypeInterface {
        $url = UriNormalizer::normalize(
            $url instanceof UriInterface ? $url : new Uri($url)
        );

        if (!isset($documentFactory)) {
            $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $documentFactory = new $class();
        }

        return static::createTypeFromXsdElement(
            $documentFactory->createFromUrl(
                $url->withFragment('')
            )[$url->getFragment()],
            $documentFactory
        );
    }

    // Create type from a schema consisting of the element's owner document
    public static function createTypeFromXsdElement(
        XsdElement $xsdElement,
        ?DocumentFactoryInterface $documentFactory = null
    ): TypeInterface {
        return self::newFromXsds(
            [ $xsdElement->ownerDocument ],
            $documentFactory ?? $xsdElement->ownerDocument->getDocumentFactory()
        )->getGlobalType(
            $xsdElement->getComponentXName()
        );
    }

    private $documentFactory_;       ///< DocumentFactoryInterface
    private $xsds_ = [];             ///< Map of URI string to Xsd
    private $cacheKey_;              ///< Key in the schema cache

    private $globalAttrs_      = []; ///< Map of XName string to Attr
    private $globalAttrGroups_ = []; ///< Map of XName string to AttrGroup
    private $globalElements_   = []; ///< Map of XName string to Element
    private $globalGroups_     = []; ///< Map of XName string to Group
    private $globalNotations_  = []; ///< Map of XName string to Notation

    ///< Map of XName string to TypeInterface
    private $globalTypes_ = [];

    private $getGlobalTypesAlreadyCalled_ = false;

    private $anyType_;                ///< ComplexType
    private $anySimpleType;           ///< PredefinedAnySimpleType

    /// Construct new schema from XSDs
    protected function __construct(
        array $xsds,
        string $cacheKey,
        ?DocumentFactoryInterface $documentFactory = null
    ) {
        $this->cacheKey_ = $cacheKey;

        $this->documentFactory_ =
            $documentFactory ?? reset($xsds)->getDocumentFactory();

        /** @throw alcamo::exception::AbsoluteUriNeeded when an XSD has a
         *  non-absolute URI. */
        $this->loadXsds($xsds);
        $this->initGlobals();
    }

    /// Return a new instance of DocumentFactory
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        if (!isset($this->documentFactory_)) {
            $this->documentFactory_ = $this->createDocumentFactory();
        }

        return $this->documentFactory_;
    }

    /// Map of URI string to alcamo::dom::xsd::Document
    public function getXsds(): array
    {
        return $this->xsds_;
    }

    /// Key in the schema cache
    public function getCacheKey(): string
    {
        return $this->cacheKey_;
    }

    public function getGlobalAttr(string $xNameString): ?AttrInterface
    {
        $globalAttr = $this->globalAttrs_[$xNameString] ?? null;

        if (!isset($globalAttr)) {
            return null;
        }

        if ($globalAttr instanceof XsdElement) {
            $globalAttr = new Attr($this, $globalAttr);
            $this->globalAttrs_[$xNameString] = $globalAttr;
        }

        return $globalAttr;
    }

    public function getGlobalAttrGroup(string $xNameString): ?AttrGroup
    {
        $globalAttrGroup = $this->globalAttrGroups_[$xNameString] ?? null;

        if (!isset($globalAttrGroup)) {
            return null;
        }

        if ($globalAttrGroup instanceof XsdElement) {
            $globalAttrGroup = new AttrGroup($this, $globalAttrGroup);
            $this->globalAttrGroups_[$xNameString] = $globalAttrGroup;
        }

        return $globalAttrGroup;
    }

    public function getGlobalElement(string $xNameString): ?Element
    {
        $globalElement = $this->globalElements_[$xNameString] ?? null;

        if (!isset($globalElement)) {
            return null;
        }

        if ($globalElement instanceof XsdElement) {
            $globalElement = new Element($this, $globalElement);
            $this->globalElements_[$xNameString] = $globalElement;
        }

        return $globalElement;
    }

    public function getGlobalGroup(string $xNameString): ?Group
    {
        $globalGroup = $this->globalGroups_[$xNameString] ?? null;

        if (!isset($globalGroup)) {
            return null;
        }

        if ($globalGroup instanceof XsdElement) {
            $globalGroup = new Group($this, $globalGroup);
            $this->globalGroups_[$xNameString] = $globalGroup;
        }

        return $globalGroup;
    }

    public function getGlobalNotation(string $xNameString): ?Notation
    {
        $globalNotation = $this->globalNotations_[$xNameString] ?? null;

        if (!isset($globalNotation)) {
            return null;
        }

        if ($globalNotation instanceof XsdElement) {
            $globalNotation = new Notation($this, $globalNotation);
            $this->globalNotations_[$xNameString] = $globalNotation;
        }

        return $globalNotation;
    }

    public function getGlobalType(string $xNameString): ?TypeInterface
    {
        $globalType = $this->globalTypes_[$xNameString] ?? null;

        if (!isset($globalType)) {
            return null;
        }

        if ($globalType instanceof XsdElement) {
            $globalType =
                AbstractType::newFromSchemaAndXsdElement($this, $globalType);

            $this->globalTypes_[$xNameString] = $globalType;
        }

        return $globalType;
    }

    /**
     * @brief Get map of all global types.
     *
     * @attention Use this method only if you really need all types (e.g. to
     * create a catalog of types). If you need only some types, use
     * getGlobalType() which is nore efficient because it will only create the
     * needed type objects.
     */
    public function getGlobalTypes(): array
    {
        if (!$this->getGlobalTypesAlreadyCalled_) {
            foreach ($this->globalTypes_ as $xNameString => $globalType) {
                if ($globalType instanceof XsdElement) {
                    $this->globalTypes_[$xNameString] =
                        AbstractType::newFromSchemaAndXsdElement(
                            $this,
                            $globalType
                        );
                }
            }
        }

        return $this->globalTypes_;
    }

    /// Instance of xsd:anyType
    public function getAnyType(): ComplexType
    {
        return $this->anyType_;
    }

    /// Instance of xsd:anySimpleType
    public function getAnySimpleType(): PredefinedAnySimpleType
    {
        return $this->anySimpleType_;
    }

    /**
     * @brief Return the type of a given element, if possible
     *
     * @warning If no information was found, the return value is identical to
     * that of getAnyType(), even when a type is explicitely given in
     * `xsi:type`. Hence an `xsi:type` referring to an unknown type is not
     * detected here. This is not an error because the only source of
     * information evaluated by this class are XSDs typically given with
     * `xsi:schemaLocation`, but [XML Schema Part 1: Structures Second
     * Edition](https://www.w3.org/TR/2004/REC-xmlschema-1-20041028/structures.html#schema-loc)
     * states that schema information may be obtained from sources other than
     * that. So a document may be valid but the schema details may not be
     * available for this class.
     *
     * This method is the primary reason why all the schema-related classes
     * were implemented.
     */
    public function lookupElementType(ExtElement $element): TypeInterface
    {
        // look up global type if explicitely given in `xsi:type`
        if (isset($element->{'xsi:type'})) {
            return $this->getGlobalType($element->{'xsi:type'})
                ?? $this->anyType_;
        }

        // look up global element, if there is one
        $elementXName = (string)$element->getXName();

        $globalElement = $this->getGlobalElement($elementXName);

        if (isset($globalElement)) {
            return $globalElement->getType();
        }

        if ($element->parentNode instanceof ExtElement) {
            /* Attempt to look up the element in the parent element type's
             *  content model. */
            $parentType = $this->lookupElementType($element->parentNode);

            if (isset($parentType)) {
                $elementDecl =
                    $parentType->getElements()[$elementXName] ?? null;

                if (isset($elementDecl)) {
                    return $elementDecl->getType();
                }
            }
        }

        // return xsd:anyType if no details could be found
        return $this->anyType_;
    }

    /// Load XSDs into @ref $xsds_
    private function loadXsds(array $xsds): void
    {
        $documentFactoryClass = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

        /* Always load XMLSchema.xsd (or get it from cache). */
        $xsds[] = $this->documentFactory_->createFromUrl(
            (new FileUriFactory())->create(
                dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
            )
        );

        /* Load indicated XSDs and XSDs referenced therein. */
        while ($xsds) {
            $xsd = array_pop($xsds);

            $url =
                (string)UriNormalizer::normalize(new Uri($xsd->documentURI));

            if (!isset($this->xsds_[$url])) {
                $this->xsds_[$url] = $xsd;

                /* Cache all provided XSDs. add() will throw if
                 * documentURI is not absolute. */
                DocumentCache::getInstance()->add($xsd);

                /* Also load imported and included XSDs. */
                foreach ($xsd->query('xsd:import|xsd:include') as $import) {
                    /* Ignore imports without schema location. */
                    if (!isset($import->schemaLocation)) {
                        continue;
                    }

                    $url = UriNormalizer::normalize(
                        $import->resolveUri($import->schemaLocation)
                    );

                    if (!isset($this->xsds_[(string)$url])) {
                        try {
                            $xsds[] =
                                $this->documentFactory_->createFromUrl($url);
                        } catch (ExceptionInterface $e) {
                            $e->addMessageContext(
                                [
                                    'atUri' => $xsd->documentURI,
                                    'atLine' => $import->getLineNo()
                                ]
                            );

                            throw $e;
                        }
                    }
                }
            }
        }
    }

    /// Initialize all global definitions
    private function initGlobals(): void
    {
        /* Setup maps of all global definitions. */
        $globalDefs = [
            'attribute'      => &$this->globalAttrs_,
            'attributeGroup' => &$this->globalAttrGroups_,
            'complexType'    => &$this->globalTypes_,
            'element'        => &$this->globalElements_,
            'group'          => &$this->globalGroups_,
            'notation'       => &$this->globalNotations_,
            'simpleType'     => &$this->globalTypes_
        ];

        foreach ($this->xsds_ as $xsd) {
            $targetNs = $xsd->documentElement->targetNamespace ?? null;

            // loop top-level XSD elements having name attributes
            foreach ($xsd->documentElement as $elem) {
                if (isset($elem->name)) {
                    $globalDefs[$elem->localName]
                        [(string)(new XName($targetNs, $elem->name))]
                        = $elem;
                }
            }
        }

        $this->anyType_ =
            $this->getGlobalType(new XName(self::XSD_NS, 'anyType'));

        /* Add `anySimpleType`. */
        $this->anySimpleType_ = new PredefinedAnySimpleType(
            $this,
            $this->anyType_
        );

        $this->globalTypes_[(string)$this->anySimpleType_->getXName()] =
            $this->anySimpleType_;

        // Add predefined XSI attributes
        foreach (self::XSI_ATTRS as $attrLocalName => $typeLocalName) {
            $attrXName = new XName(self::XSI_NS, $attrLocalName);

            $this->globalAttrs_[(string)$attrXName] =
                new PredefinedAttr(
                    $this,
                    $attrXName,
                    $this->getGlobalType(
                        new XName(self::XSD_NS, $typeLocalName)
                    )
                );
        }
    }
}
