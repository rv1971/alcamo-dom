<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    DocumentCache,
    DocumentFactoryInterface,
    HavingDocumentFactoryInterface,
    HavingDocumentFactoryTrait
};
use alcamo\dom\decorated\{DocumentFactory, Element as XsdElement};
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
use alcamo\xml\{NamespaceConstantsInterface, XName};
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
 * The factory methods take a schema from the cache if possible. Cache keys
 * are built from a sorted list of normalized absolute URIs of XSDs.
 * - Hence different collections of URIs that represent the same XSD resources
 *   are recognized as equal.
 * - Different collections of URIs that represent the same schema because they
 *   contain redundant URIs of XSD resources which are imported by other XSD
 *   instead are *not* recognized as equal.
 *
 * @date Last reviewed 2025-11-07
 */
class Schema implements
    HavingDocumentFactoryInterface,
    NamespaceConstantsInterface
{
    use HavingDocumentFactoryTrait {
        __construct as initDocumentFactory;
    }

    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;

    /// Predefined XSI attributes
    public const PREDEFINED_XSI_ATTRS =
        [ 'nil' => 'boolean', 'type' => 'QName' ];

    private $xsds_ = [];             ///< Map of URI string to Xsd
    private $topXsds_ = [];          ///< Map of URI string to Xsd
    private $nonTopXsds_ = [];       ///< Map of URI string to Xsd

    private $globalAttrs_      = []; ///< Map of XName string to Attr
    private $globalAttrGroups_ = []; ///< Map of XName string to AttrGroup
    private $globalElements_   = []; ///< Map of XName string to Element
    private $globalGroups_     = []; ///< Map of XName string to Group
    private $globalNotations_  = []; ///< Map of XName string to Notation

    ///< Map of XName string to TypeInterface
    private $globalTypes_ = [];

    private $getGlobalAttrsAlreadyCalled_    = false;
    private $getGlobalElementsAlreadyCalled_ = false;
    private $getGlobalTypesAlreadyCalled_    = false;

    private $anyType_;                ///< ComplexType
    private $anySimpleType;           ///< PredefinedAnySimpleType

    /**
     * @brief Construct new schema from XSDs
     *
     * @param $xsds alcamo::dom::Document objects containing XSDs to include
     * into the schema.
     */
    public function __construct(array $xsds)
    {
        $this->initDocumentFactory(
            $xsds ? reset($xsds)->getDocumentFactory() : null
        );

        $cache = SchemaCache::getInstance();

        $cache->add($cache->createKey($xsds), $this);

        /* Always load XMLSchema.xsd (or get it from cache). */
        $xsds[] = $this->documentFactory_->createFromUri(
            (new FileUriFactory())->create(
                dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
            )
        );

        /** @throw alcamo::exception::AbsoluteUriNeeded when an XSD has a
         *  non-absolute URI. */
        $this->addXsds($xsds);
    }

    /// Map of URI string to alcamo::dom::Document
    public function getXsds(): array
    {
        return $this->xsds_;
    }

    /**
     * @brief Map of URI string to alcamo::dom::Document
     *
     * Unlike getXsds(), this does not contain the XSDs `<include>`d by other
     * XSDs. Used in alcamo::dom::schema::FixedSchemaSimpleTypeValidator to
     * create a list of `<import>` elements withput duplicate namespaces.
     */
    public function getTopXsds(): array
    {
        return $this->topXsds_;
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

    /**
     * @brief Get map of XName strings to attributes for all global attributes
     *
     * @attention Use this method only if you really need all attributes
     * (e.g. to create a catalog of attributes). If you need only some
     * attributes, use getGlobalAttr() which is much more efficient because it
     * will only create the needed attr objects.
     */
    public function getGlobalAttrs(): array
    {
        if (!$this->getGlobalAttrsAlreadyCalled_) {
            foreach ($this->globalAttrs_ as $xNameString => $globalAttr) {
                if ($globalAttr instanceof XsdElement) {
                    $this->globalAttrs_[$xNameString] =
                        new Attr($this, $globalAttr);
                }
            }
        }

        return $this->globalAttrs_;
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

    /**
     * @brief Get map of XName strings to elements for all global elements
     *
     * @attention Use this method only if you really need all elements
     * (e.g. to create a catalog of elements). If you need only some elements,
     * use getGlobalElement() which is much more efficient because it will
     * only create the needed element objects.
     */
    public function getGlobalElements(): array
    {
        if (!$this->getGlobalElementsAlreadyCalled_) {
            foreach ($this->globalElements_ as $xNameString => $globalElement) {
                if ($globalElement instanceof XsdElement) {
                    $this->globalElements_[$xNameString] =
                        new Element($this, $globalElement);
                }
            }
        }

        return $this->globalElements_;
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
     * @brief Get map of XName strings to types for all global types
     *
     * @attention Use this method only if you really need all types (e.g. to
     * create a catalog of types). If you need only some types, use
     * getGlobalType() which is much more efficient because it will only
     * create the needed type objects.
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

    /// Get an instance of xsd:anyType
    public function getAnyType(): ComplexType
    {
        return $this->anyType_;
    }

    /// Get an instance of xsd:anySimpleType
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

    /**
     * @brief Add XSDs to the schema
     *
     * @param $uris URIs of XSDs to include into the schema.
     */
    public function addUris(iterable $uris): void
    {
        $xsds = [];

        foreach ($uris as $uri) {
            $xsds[] = $this->documentFactory_->createFromUri($uri);
        }

        $this->addXsds($xsds);
    }

    /// Add XSDs to the schema
    public function addXsds(array $xsds): void
    {
        $cache = SchemaCache::getInstance();

        $cacheKey = $cache->createKey($xsds);

        if (!isset($cache[$cacheKey])) {
            $cache->add($cacheKey, $this);
        }

        $processedXsds = [];

        /* Load indicated XSDs and XSDs referenced therein. */
        while ($xsds) {
            $xsd = array_pop($xsds);

            $uri =
                (string)UriNormalizer::normalize(new Uri($xsd->documentURI));

            if (!isset($this->xsds_[$uri])) {
                $processedXsds[$uri] = $xsd;

                /* Cache all provided XSDs. add() will throw if
                 * documentURI is not absolute. */
                DocumentCache::getInstance()->add($xsd);

                /* Also load imported and included XSDs. */
                foreach (
                    $xsd->documentElement
                        ->query('xsd:import|xsd:include') as $import
                ) {
                    /* Ignore imports without schema location. */
                    if (!isset($import->schemaLocation)) {
                        continue;
                    }

                    $uri = UriNormalizer::normalize(
                        $import->resolveUri($import->schemaLocation)
                    );

                    if (!isset($processedXsds[(string)$uri])) {
                        try {
                            $xsds[] =
                                $this->documentFactory_->createFromUri($uri);
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

        $this->initGlobals($processedXsds);
    }

    /// Initialize all global definitions
    private function initGlobals(array $xsds): void
    {
        $this->topXsds_ += $xsds;
        $this->xsds_ += $xsds;

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

        foreach ($xsds as $xsd) {
            $targetNs = $xsd->documentElement->targetNamespace ?? null;

            // loop top-level XSD elements having name attributes
            foreach ($xsd->documentElement as $elem) {
                /* Remove XSDs included in other XSDs from
                 * $this->topXsds_. This must be done here, not in addXsds(),
                 * because the XSDs given to the latter might redundantly
                 * contain XSDs also included in other XSDs. */
                if (
                    $elem->namespaceURI == self::XSD_NS
                    && $elem->localName == 'include'
                ) {
                    $uri = (string)UriNormalizer::normalize(
                        $elem->resolveUri($elem->schemaLocation)
                    );

                    $this->nonTopXsds_[$uri] = $xsd;
                    unset($this->topXsds_[$uri]);
                }

                if (isset($elem->name)) {
                    $globalDefs[$elem->localName]
                        [(string)(new XName($targetNs, $elem->name))]
                        = $elem;
                }
            }
        }

        /* Remove top XSDs which have become non-top because included by the
         * new XSDs. */
        $this->topXsds_ = array_diff_key($this->topXsds_, $this->nonTopXsds_);

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
        foreach (
            self::PREDEFINED_XSI_ATTRS as $attrLocalName => $typeLocalName
        ) {
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
