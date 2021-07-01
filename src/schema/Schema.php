<?php

namespace alcamo\dom\schema;

use GuzzleHttp\Psr7\UriResolver;
use alcamo\dom\ConverterPool;
use alcamo\dom\extended\{Document, DocumentFactory, Element as ExtElement};
use alcamo\dom\schema\component\{
    AbstractComponent,
    AbstractSimpleType,
    AbstractType,
    Attr,
    AttrGroup,
    ComplexType,
    Element,
    Group,
    Notation,
    PredefinedAttr,
    PredefinedSimpleType,
    SimpleType,
    TypeInterface
};
use alcamo\dom\xsd\{Document as Xsd, Element as XsdElement};
use alcamo\exception\AbsoluteUriNeeded;
use alcamo\ietf\{Uri, UriNormalizer};
use alcamo\xml\XName;

/**
 * @warning `\<redefine>` is not supported.
 */
class Schema
{
    private static $schemaCache_ = [];

    /** This works even if a document has no `xsi:schemaLocation` attribute,
     *  in which case the schema has only the predefined components in the xsm
     *  and the xsd namespace. */
    public static function newFromDocument(\DOMDocument $doc): self
    {
        $urls = [];

        $baseUri = new Uri($doc->baseURI);

        if (!Uri::isAbsolute($baseUri)) {
            $baseUri = $baseUri->withScheme('file');
        }

        $schemaLocation = $doc->documentElement
            ->getAttributeNodeNS(Document::XSI_NS, 'schemaLocation');

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

    public static function newFromXsds(array $xsds): self
    {
        $urls = [];

        foreach ($xsds as $xsd) {
            $url = new Uri($xsd->documentURI);

            if (!Uri::isAbsolute($url)) {
                /** @throw AbsoluteUriNeeded when attempting to use a
                 * non-absolute URL as a cache key. */
                throw new AbsoluteUriNeeded($xsd->documentURI);
            }

            // normalize URL for use by caching
            $xsd->documentURI = (string)UriNormalizer::normalize($url);

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

    ///< Map of XName string to AbstractType
    private $globalTypes_ = [];

    private $getGlobalTypesAlreadyCalled_ = false;

    private $anyType_;                ///< ComplexType
    private $anySimpleType;           ///< PredefinedSimpleType

    /** @throw AbsoluteUriNeeded when an XSD has a non-absolute URI. */
    private function __construct(array $xsds)
    {
        $this->loadXsds($xsds);
        $this->initGlobals();
    }

    public function getXsds(): array
    {
        return $this->xsds_;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey_;
    }

    public function getGlobalAttr(string $xNameString): ?AbstractComponent
    {
        if (!isset($this->globalAttrs_[$xNameString])) {
            return null;
        }

        if ($this->globalAttrs_[$xNameString] instanceof XsdElement) {
            $this->globalAttrs_[$xNameString] =
                new Attr($this, $this->globalAttrs_[$xNameString]);
        }

        return $this->globalAttrs_[$xNameString];
    }

    public function getGlobalAttrGroup(string $xNameString): ?AttrGroup
    {
        if (!isset($this->globalAttrGroups_[$xNameString])) {
            return null;
        }

        if ($this->globalAttrGroups_[$xNameString] instanceof XsdElement) {
            $this->globalAttrGroups_[$xNameString] =
                new AttrGroup($this, $this->globalAttrGroups_[$xNameString]);
        }

        return $this->globalAttrGroups_[$xNameString];
    }

    public function getGlobalElement(string $xNameString): ?Element
    {
        if (!isset($this->globalElements_[$xNameString])) {
            return null;
        }

        if ($this->globalElements_[$xNameString] instanceof XsdElement) {
            $this->globalElements_[$xNameString] =
                new Element($this, $this->globalElements_[$xNameString]);
        }

        return $this->globalElements_[$xNameString];
    }

    public function getGlobalGroup(string $xNameString): ?Group
    {
        if (!isset($this->globalGroups_[$xNameString])) {
            return null;
        }

        if ($this->globalGroups_[$xNameString] instanceof XsdElement) {
            $this->globalGroups_[$xNameString] =
                new Group($this, $this->globalGroups_[$xNameString]);
        }

        return $this->globalGroups_[$xNameString];
    }

    public function getGlobalNotation(string $xNameString): ?Notation
    {
        if (!isset($this->globalNotations_[$xNameString])) {
            return null;
        }

        if ($this->globalNotations_[$xNameString] instanceof XsdElement) {
            $this->globalNotations_[$xNameString] =
                new Notation($this, $this->globalNotations_[$xNameString]);
        }

        return $this->globalNotations_[$xNameString];
    }

    public function getGlobalType(string $xNameString): ?TypeInterface
    {
        $comp = $this->globalTypes_[$xNameString] ?? null;

        if (!isset($comp)) {
            return null;
        }

        // create type from XSD element upon first invocation
        if ($comp instanceof XsdElement) {
            $this->globalTypes_[$xNameString] =
                $comp->localName == 'simpleType'
                ? AbstractSimpleType::newFromSchemaAndXsdElement($this, $comp)
                : new ComplexType($this, $comp);
        }

        return $this->globalTypes_[$xNameString];
    }

    /**
     * @brief Get map of all global types.
     *
     * Use this method only if you really need all types (e.g. to create a
     * catalog of types). If you need only some types, use getGlobalType()
     * which is nore efficient because it will onyl create the neededtype
     * objects.
     */
    public function getGlobalTypes(): array
    {
        if (!$this->getGlobalTypesAlreadyCalled_) {
            foreach ($this->globalTypes_ as $xNameString => $comp) {
                if ($comp instanceof XsdElement) {
                    $this->globalTypes_[$xNameString] =
                        $comp->localName == 'simpleType'
                        ? AbstractSimpleType::newFromSchemaAndXsdElement(
                            $this,
                            $comp
                        )
                        : new ComplexType($this, $comp);
                }
            }
        }

        return $this->globalTypes_;
    }

    public function getAnyType(): ComplexType
    {
        return $this->anyType_;
    }

    public function getAnySimpleType(): PredefinedSimpleType
    {
        return $this->anySimpleType_;
    }

    public function lookupElementType(ExtElement $element): ?AbstractType
    {
        // look up global type if explicitely given in `xsi:type`
        if ($element->hasAttributeNS(Document::XSI_NS, 'type')) {
            return $this->getGlobalType(
                ConverterPool::toXName(
                    $element->getAttributeNS(Document::XSI_NS, 'type'),
                    $element
                )
            );
        }

        // look up global element, if there is one
        $elementXName = $element->getXName();

        $globalElement = $this->getGlobalElement($elementXName);

        if (isset($globalElement)) {
            return $globalElement->getType();
        }

        if ($element->parentNode instanceof ExtElement) {
            // attempt to look up element in parent element type's content model
            $parentType = $this->lookupElementType($element->parentNode);

            if (isset($parentType)) {
                $elementDecl =
                    $parentType->getElements()[(string)$elementXName] ?? null;

                if (isset($elementDecl)) {
                    return $elementDecl->getType();
                }
            }
        }

        return $this->anyType_;
    }

    protected static function createXsd(string $url): Xsd
    {
        return (new DocumentFactory())
            ->createFromUrl($url, Xsd::class, null, true);
    }

    private function loadXsds(array $xsds)
    {
        // always load XMLSchema.xsd
        $xmlSchemaXsd = static::createXsd(
            'file://' . realpath(
                __DIR__ . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR
                . '..' . DIRECTORY_SEPARATOR
                . 'xsd' . DIRECTORY_SEPARATOR
                . 'XMLSchema.xsd'
            )
        );

        $xsds[] = $xmlSchemaXsd;

        // load indicated XSDs and XSDs referenced therein
        while ($xsds) {
            $xsd = array_pop($xsds);

            if (!isset($this->xsds_[$xsd->documentURI])) {
                $this->xsds_[$xsd->documentURI] = $xsd;

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

    private function initGlobals()
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
            $this->getGlobalType(new XName(Document::XSD_NS, 'anyType'));

        // Add `anySimpleType`.
        $anySimpleTypeXName = new XName(Document::XSD_NS, 'anySimpleType');

        $this->anySimpleType_ = new PredefinedSimpleType(
            $this,
            $anySimpleTypeXName,
            $this->anyType_
        );

        $this->globalTypes_[(string)$anySimpleTypeXName] =
            $this->anySimpleType_;

        // Add `xsi:type` to be `xsd:QName` if undefined.
        $xsiTypeXName = new XName(Document::XSI_NS, 'type');

        if (!isset($this->globalAttrs_[(string)$xsiTypeXName])) {
            $this->globalAttrs_[(string)$xsiTypeXName] =
                new PredefinedAttr(
                    $this,
                    $xsiTypeXName,
                    $this->getGlobalType(new XName(Document::XSD_NS, 'QName'))
                );
        }
    }
}
