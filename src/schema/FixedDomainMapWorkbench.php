<?php

namespace alcamo\dom\schema;

use alcamo\collection\{ReadonlyCollection, Rfc4514Serializer};
use alcamo\dom\{
    DocumentFactoryInterface,
    DocumentValidator,
    HavingDocumentFactoryInterface,
    HavingDocumentFactoryTrait
};
use alcamo\dom\psvi\{Document, DocumentFactory};
use alcamo\dom\schema\component\{AbstractXsdComponent, Element as ElementDecl};
use alcamo\rdfa\ImmutableRdfaData;

/**
 * @brief Manage a key-value data structure with fixed keys
 *
 * Rules about allowed keys, required keys, canonical order of keys and data
 * types associated to the keys are all taken from an XML Schema document.
 *
 * @invariant Immutable class.
 *
 * @date Last reviewed 2026-11-05
 */
class FixedDomainMapWorkbench implements HavingDocumentFactoryInterface
{
    use HavingDocumentFactoryTrait {
        __construct as initDocumentFactory;
    }

    /// Validate data in filterIterable()
    public const VALIDATE = 1;

    /// OR-Combination of the above constants
    public const DEFAULT_FLAGS = self::VALIDATE;

    /// Default class used to create a document prototype
    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;

    /// Default class to get a default schema from
    public const DEFAULT_SCHEMA_FACTORY_CLASS = SchemaFactory::class;

    /// Default collection class to create in create*() methods
    public const DEFAULT_COLLECTION_CLASS = ReadonlyCollection::class;

    private $xsdUri_;       ///< ?string
    private $xsdText_;      ///< ?string
    private $docPrototype_; ///< Document
    private $attrs_;        ///< map of string keys to Attr objects
    private $flags_;        ///< int

    /**
     * Unlike newFromXsdText(), there the element ID (as part of the URI) is
     * not optional. This is because
     * alcamo::dom::Schema::createElementFromUri() is optimized to avoid
     * actually loading the XSD if possible, while loading the XSD would be
     * necessary to find out which is the first element.
     */
    public static function newFromElementUri(
        string $uri,
        ?int $flags = null,
        ?Schema $schema = null,
        ?DocumentFactoryInterface $documentFactory = null
    ): self {
        if (!isset($schema)) {
            $class = static::DEFAULT_SCHEMA_FACTORY_CLASS;
            $schema = $class::getMainInstance()->getMainSchema();
        }

        return new static(
            $schema->createElementFromUri($uri),
            $flags,
            $documentFactory
        );
    }

    /**
     * If $elementId is not given, the first element in the XSD is used.
     */
    public static function newFromXsdText(
        string $xsdText,
        ?string $elementId = null,
        ?int $flags = null,
        ?Schema $schema = null,
        ?DocumentFactoryInterface $documentFactory = null
    ): self {
        if (!isset($schema)) {
            $class = static::DEFAULT_SCHEMA_FACTORY_CLASS;
            $schema = $class::getMainInstance()->getMainSchema();
        }

        if (!isset($documentFactory)) {
            $class = static::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $documentFactory = new $class();
        }

        $xsd = $documentFactory->createFromXmlText($xsdText, null, true);

        return new static(
            $schema->getGlobalElement(
                (isset($elementId)
                 ? $xsd[$elementId]
                 : $xsd->query('/*/xsd:element[1]')[0])
                    ->getComponentXName()
            ),
            $flags,
            $xsdText,
            $documentFactory
        );
    }

    /**
     * @param $elementDecl Declaration of an element whose attributes will be
     * used in filterIterable().
     *
     * @param $flags OR-Combination of the above constants. [Default
     * alcamo::dom::FixedDomainMapWorkbench::DEFAULT_FLAGS]
     *
     * @param $xsdText Text of the XSD where $elementDecl comes from. Needed
     * if the XSD for validation cannot be retrieved via a URI, e.g. because
     * it has been created dynamically.
     */
    public function __construct(
        ElementDecl $elementDecl,
        ?int $flags = null,
        ?string $xsdText = null,
        ?DocumentFactoryInterface $documentFactory = null
    ) {
        $this->xsdUri_ =
            $elementDecl->getXsdElement()->ownerDocument->documentURI;
        $this->xsdText_ = $xsdText;

        $this->initDocumentFactory($documentFactory);

        $elementXName = $elementDecl->getXName();

        $this->docPrototype_ = $this->documentFactory_->createFromXmlText(
            "<?xml version='1.0' encoding='UTF-8'?>"
                . "<{$elementXName->getLocalName()} "
                . "xmlns='{$elementXName->getNsName()}'/>",
            null,
            true
        );

        $this->attrs_ =
            $this->docPrototype_->documentElement->getType()->getAttrs();

        $this->flags_ = $flags ?? static::DEFAULT_FLAGS;
    }

    /// URI of the XSD containing the element declaration, if available
    public function getXsdUri(): ?string
    {
        return $this->xsdUri_;
    }

    /// Text of the XSD containing the element declaration, if available
    public function getXsdText(): ?string
    {
        return $this->xsdText_;
    }

    /// Document consisting of one element without attributes
    public function getDocPrototype(): Document
    {
        return $this->docPrototype_;
    }

    /// Map of keys to attribute declarations
    public function getAttrs(): array
    {
        return $this->attrs_;
    }

    /// Flags given to the constructor
    public function getFlags(): int
    {
        return $this->flags_;
    }

    /**
     * @brief Filter data by XML Schema definition
     *
     * Create an XML document consisting of a single element and add the
     * content of $data as attributes. Validate the document, if requested by
     * the $flags given to the constructor, and return an iterable made of
     * the attributes read from the document, in the order specified by the
     * element type's declaration. This offers the following features:
     * - If validation is done, this method throws an exception if
     *   + a required key is missing,
     *   + unknown keys are present, unless the element declaration allows for
     *     any attribute,
     *   + the value of a key does not satisfy some restriction imposed by the
     *     schema.
     * - The result only contains the keys specified in the schema, and in the
     *   order of specification.
     * - Any items with value `null` are removed.
     * - Values are created from attribute defaults, if needed.
     * - Values are converted according to their XSD types, as specified in the
     *   document class.
     *
     * @param $data Input data.
     *
     * @param $result array or iterable object the filtered data are added
     * to. If not given, start from an empty array.
     *
     * @return $result containing filtered data.
     */
    public function filterIterable(
        iterable $data,
        ?iterable &$result = null
    ): iterable {
        $doc = clone $this->docPrototype_;

        $element = $doc->documentElement;

        foreach ($data as $key => $value) {
            $element->$key = $value;
        }

        if ($this->flags_ & self::VALIDATE) {
            if (isset($this->xsdText_)) {
                (new DocumentValidator())
                    ->validateAgainstXsdText($doc, $this->xsdText_);
            } else {
                (new DocumentValidator())
                    ->validateAgainstXsdUri($doc, $this->xsdUri_);
            }
        }

        if (!isset($result)) {
            $result = [];
        }

        /* Iterate over attributes of the XSD type, to maintain their order
         * and to ignore any unspecified attributes. */
        foreach ($this->attrs_ as $key => $attr) {
            if (isset($element->$key)) {
                $result[$key] = $element->$key;
            } elseif (
                $attr instanceof AbstractXsdComponent
                && isset($attr->getXsdElement()->default)
            ) {
                $element->$key = $attr->getXsdElement()->default;

                /* May differ from `default` because conversion may be
                 * applied. */
                $result[$key] = $element->$key;
            }
        }

        return $result;
    }

    /**
     * @brief Create a collection object from filtered data
     *
     * Filter input data as described in filterIterable() and return an object
     * of the requested class.
     *
     * @param $data Input data passed to filterIterable().
     *
     * @param $collectionClass Class of the desired result object. [Default
     * alcamo::dom::FixedDomainMapWorkbench::DEFAULT_COLLECTION_CLASS]
     */
    public function createCollectionFromIterable(
        iterable $data,
        ?string $collectionClass = null
    ): object {
        if (!isset($collectionClass)) {
            $collectionClass = static::DEFAULT_COLLECTION_CLASS;
        }

        return new $collectionClass(static::filterIterable($data));
    }

    /**
     * @brief Create a collection object from filtered data in RDF 4514 format
     *
     * Deserialize $text as an RFC 4514 string, filter the data as described
     * in filterIterable() and return an object of the requested class.
     *
     * @param $text Input data in RDF 4514 format
     *
     * @param $collectionClass Class of the desired result object. [Default
     * alcamo::dom::FixedDomainMapWorkbench::DEFAULT_COLLECTION_CLASS]
     */
    public function createCollectionFromString(
        string $text,
        ?string $collectionClass = null
    ): object {
        return $this->createCollectionFromIterable(
            (new Rfc4514Serializer())->deserialize($text),
            $collectionClass
        );
    }

    /// RDFa data in the schema for the type of attribute $key
    public function getRdfaData(string $key): ImmutableRdfaData
    {
        return $this->attrs_[$key]->getXsdElement()->getRdfaData();
    }
}
