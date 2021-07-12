<?php

namespace alcamo\dom\schema;

use GuzzleHttp\Psr7\UriResolver;
use alcamo\dom\ConverterPool;
use alcamo\dom\extended\{DocumentFactory, Element as ExtElement};
use alcamo\dom\schema\component\{
    AbstractComponent,
    AbstractSimpleType,
    Attr,
    AttrGroup,
    AttrInterface,
    ComplexType,
    Element,
    Group,
    Notation,
    PredefinedAttr,
    PredefinedSimpleType,
    TypeInterface
};
use alcamo\dom\xsd\{Document as Xsd, Element as XsdElement};
use alcamo\exception\AbsoluteUriNeeded;
use alcamo\ietf\{Uri, UriNormalizer};
use alcamo\xml\XName;

/**
 * @namespace alcamo::dom::schema
 *
 * @brief Classes to model an XML %Schema
 */

/**
 * @brief Complete XML Schema
 *
 * @warning `\<redefine>` is not supported.
 *
 * @date Last reviewed 2021-07-10
 */
class Schema
{
    private static $schemaCache_ = [];

    /**
     * @brief Construct new schema or get it from cache
     *
     * This method works even if a document has no `xsi:schemaLocation`
     *  attribute, in which case the schema has only the predefined components
     *  in the `xml` and the `xsd` namespaces. */
    public static function newFromDocument(\DOMDocument $doc): self
    {
        $urls = [];

        $baseUri = new Uri($doc->baseURI);

        /* Since PHP does not store the file:// prefix in baseURI, prepend it
         * when encountering a document without scheme. */
        if (!Uri::isAbsolute($baseUri)) {
            $baseUri = $baseUri->withScheme('file');
        }

        $schemaLocation = $doc->documentElement
            ->getAttributeNodeNS(Xsd::XSI_NS, 'schemaLocation');

        if ($schemaLocation) {
            foreach (
                ConverterPool::pairsToMap(
                    $schemaLocation,
                    $schemaLocation
                ) as $nsName => $url
            ) {
                $urls[] = UriResolver::resolve($baseUri, new Uri($url));
            }
        }

        return static::newFromUrls($urls);
    }

    /// Construct new schema or get it from cache
    public static function newFromUrls(iterable $urls): self
    {
        $normalizedUrls = [];

        foreach ($urls as $url) {
            $normalizedUrls[] =
                (string)UriNormalizer::normalize(
                    $url instanceof Uri ? $url : new Uri($url)
                );
        }

        $cacheKey = implode(' ', $normalizedUrls);

        if (!isset(self::$schemaCache_[$cacheKey])) {
            $xsds = [];

            foreach ($normalizedUrls as $url) {
                $xsds[] = static::createXsd($url);
            }

            $schema = new static($xsds);
            $schema->cacheKey_ = $cacheKey;

            self::$schemaCache_[$cacheKey] = $schema;
        }

        return self::$schemaCache_[$cacheKey];
    }

    /// Construct new schema or get it from cache
    public static function newFromXsds(array $xsds): self
    {
        $urls = [];

        foreach ($xsds as $xsd) {
            $url = UriNormalizer::normalize(new Uri($xsd->documentURI));

            if (!Uri::isAbsolute($url)) {
                /** @throw AbsoluteUriNeeded when attempting to use a
                 * non-absolute URL as a cache key. */
                throw new AbsoluteUriNeeded($xsd->documentURI);
            }

            // normalize URL for use by caching
            $xsd->documentURI = (string)$url;

            $urls[] = $xsd->documentURI;
        }

        $cacheKey = implode(' ', $urls);

        if (!isset(self::$schemaCache_[$cacheKey])) {
            $schema = new static($xsds);
            $schema->cacheKey_ = $cacheKey;

            self::$schemaCache_[$cacheKey] = $schema;
        }

        return self::$schemaCache_[$cacheKey];
    }

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
    private $anySimpleType;           ///< PredefinedSimpleType

    /// Construct new schema from XSDs
    protected function __construct(array $xsds)
    {
        /** @throw AbsoluteUriNeeded when an XSD has a non-absolute URI. */
        $this->loadXsds($xsds);
        $this->initGlobals();
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
            $globalType = $globalType->localName == 'simpleType'
                ? AbstractSimpleType::newFromSchemaAndXsdElement(
                    $this,
                    $globalType
                )
                : new ComplexType($this, $globalType);

            $this->globalTypes_[$xNameString] = $globalType;
        }

        return $globalType;
    }

    /**
     * @brief Get map of all global types.
     *
     * @note Use this method only if you really need all types (e.g. to create
     * a catalog of types). If you need only some types, use getGlobalType()
     * which is nore efficient because it will only create the needed type
     * objects.
     */
    public function getGlobalTypes(): array
    {
        if (!$this->getGlobalTypesAlreadyCalled_) {
            foreach ($this->globalTypes_ as $xNameString => $globalType) {
                if ($globalType instanceof XsdElement) {
                    $this->globalTypes_[$xNameString] =
                        $globalType->localName == 'simpleType'
                        ? AbstractSimpleType::newFromSchemaAndXsdElement(
                            $this,
                            $globalType
                        )
                        : new ComplexType($this, $globalType);
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
    public function getAnySimpleType(): PredefinedSimpleType
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
     * This method is the primary reason why the entire class was implemented.
     */
    public function lookupElementType(ExtElement $element): TypeInterface
    {
        // look up global type if explicitely given in `xsi:type`
        if ($element->hasAttributeNS(Xsd::XSI_NS, 'type')) {
            return $this->getGlobalType(
                ConverterPool::toXName(
                    $element->getAttributeNS(Xsd::XSI_NS, 'type'),
                    $element
                )
            ) ?? $this->anyType_;
        }

        // look up global element, if there is one
        $elementXName = $element->getXName();

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
                    $parentType->getElements()[(string)$elementXName] ?? null;

                if (isset($elementDecl)) {
                    return $elementDecl->getType();
                }
            }
        }

        // return xsd:anyType if no details could be found
        return $this->anyType_;
    }

    protected static function createXsd(string $url): Xsd
    {
        return (new DocumentFactory())
            ->createFromUrl($url, Xsd::class, null, true);
    }

    /// Load XSDs into @ref $xsds_
    private function loadXsds(array $xsds): void
    {
        // always load XMLSchema.xsd (or get it from cache)
        $xmlSchemaXsd = static::createXsd(
            'file://' . realpath(
                dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
            )
        );

        $xsds[] = $xmlSchemaXsd;

        // load indicated XSDs and XSDs referenced therein
        while ($xsds) {
            $xsd = array_pop($xsds);

            $url =
                (string)UriNormalizer::normalize(new Uri($xsd->documentURI));

            if (!isset($this->xsds_[$url])) {
                $this->xsds_[$url] = $xsd;

                /* Cache all XSDs. addToCache() will throw if documentURI is
                 * not absolute. */
                DocumentFactory::addToCache($xsd);

                // Also load imported XSDs.
                foreach ($xsd->query('xsd:import|xsd:include') as $import) {
                    /** Ignore imports without schema location. */
                    if (!isset($import->schemaLocation)) {
                        continue;
                    }

                    $url = $import->resolveUri($import->schemaLocation);

                    if (!isset($this->xsds_[(string)$url])) {
                        $xsds[] = $this->createXsd($url);
                    }
                }
            }
        }
    }

    /// Initialize all global definitions
    private function initGlobals(): void
    {
        // setup maps of all global definitions
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
            $targetNs = $xsd->documentElement->targetNamespace;

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
            $this->getGlobalType(new XName(Xsd::XSD_NS, 'anyType'));

        // Add `anySimpleType`.
        $anySimpleTypeXName = new XName(Xsd::XSD_NS, 'anySimpleType');

        $this->anySimpleType_ = new PredefinedSimpleType(
            $this,
            $anySimpleTypeXName,
            $this->anyType_
        );

        $this->globalTypes_[(string)$anySimpleTypeXName] =
            $this->anySimpleType_;

        // Add `xsi:type` to be `xsd:QName` if undefined.
        $xsiTypeXName = new XName(Xsd::XSI_NS, 'type');

        if (!isset($this->globalAttrs_[(string)$xsiTypeXName])) {
            $this->globalAttrs_[(string)$xsiTypeXName] =
                new PredefinedAttr(
                    $this,
                    $xsiTypeXName,
                    $this->getGlobalType(new XName(Xsd::XSD_NS, 'QName'))
                );
        }
    }
}
