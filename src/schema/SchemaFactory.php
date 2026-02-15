<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    ConverterPool,
    Document,
    DocumentFactoryInterface,
    HavingDocumentFactoryInterface,
    HavingDocumentFactoryTrait
};
use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\component\TypeInterface;
use alcamo\uri\{FileUriFactory, Uri};
use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief Factory for XML Schemas
 *
 * Features caching of schemas.
 *
 * @date Last reviewed 2025-12-10
 */
class SchemaFactory implements
    HavingDocumentFactoryInterface,
    NamespaceConstantsInterface
{
    use HavingDocumentFactoryTrait;

    public const SCHEMA_CLASS = Schema::class;

    /**
     * @param $documentFactory Factory used to create documents.
     */
    public function __construct(
        ?DocumentFactoryInterface $documentFactory = null
    ) {
        if (isset($documentFactory)) {
            $this->documentFactory_ = $documentFactory;
        } else {
            $schemaClass = static::SCHEMA_CLASS;
            $class = $schemaClass::DEFAULT_DOCUMENT_FACTORY_CLASS;

            $this->documentFactory_ = new $class();
        }
    }

    /**
     * @brief Construct new schema or get it from cache
     *
     * @param $doc XML document for which the schema is to be created.
     *
     * This method works even if a document has no `xsi:schemaLocation`
     *  attribute, in which case the schema has only the predefined components
     *  in the `xml` and the `xsd` namespaces. */
    public function createFromDocument(
        Document $doc,
        ?DocumentFactoryInterface $documentFactory = null
    ): Schema {
        $uris = [];

        $schemaLocation = $doc->documentElement
            ->getAttributeNodeNS(self::XSI_NS, 'schemaLocation');

        if ($schemaLocation) {
            foreach (
                ConverterPool::pairsToMap($schemaLocation) as $nsName => $uri
            ) {
                $uris[] = $doc->documentElement->resolveUri(new Uri($uri));
            }
        }

        return $this->createFromUris(
            $uris,
            $documentFactory ?? $doc->getDocumentFactory()
        );
    }

    /**
     * @brief Construct new schema or get it from cache
     *
     * @param $uris URIs of XSDs to include into the schema.
     */
    public function createFromUris(iterable $uris): Schema
    {
        $cache = SchemaCache::getInstance();

        $schema = $cache[$cache->createKey($uris)] ?? null;

        if (isset($schema)) {
            return $schema;
        }

        $xsds = [];

        foreach ($uris as $uri) {
            $xsds[] = $this->documentFactory_->createFromUri($uri);
        }

        $class = static::SCHEMA_CLASS;

        return new $class($xsds);
    }

    /**
     * @brief Construct new schema or get it from cache
     *
     * @param $xsds alcamo::dom::Document objects containing XSDs to include
     * into the schema.
     */
    public function createFromXsds(array $xsds): Schema
    {
        $cache = SchemaCache::getInstance();

        $schema = $cache[$cache->createKey($xsds)] ?? null;

        if (isset($schema)) {
            return $schema;
        }

        $class = static::SCHEMA_CLASS;

        return new $class($xsds);
    }

    /// Create a type from an URI reference indicating an XSD element by ID
    public function createTypeFromUri($uri): TypeInterface
    {
        /** If the target namespace of the indicated document is the
         *  XSD namespace, take the corresponding XSD type without acually
         *  accessing http://www.w3.org/2001/XMLSchema. */
        $uri = $this->documentFactory_->resolveUri($uri);

        if (
            TargetNsCache::getInstance()[$uri->withFragment('')]
                == self::XSD_NS
        ) {
            return $this->getMainSchema()
                ->getGlobalType(self::XSD_NS . ' ' . $uri->getFragment());
        }

        $xsdElement = $this->documentFactory_->createFromUri($uri);

        return static::createTypeFromXsdElement($xsdElement);
    }

    /// Create type from a schema consisting of the element's owner document
    public function createTypeFromXsdElement(
        XsdElement $xsdElement
    ): TypeInterface {
        return $this->createFromXsds([ $xsdElement->ownerDocument ])
            ->getGlobalType($xsdElement->getComponentXName());
    }

    /// Create schema from all XSDs in directory and its subdirectories
    public function createFromDirectory(string $dir): Schema
    {
        $uris = [];

        $fileUriFactory = new FileUriFactory();

        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir)
            ) as $path => $fileInfo
        ) {
            if ($fileInfo->getExtension() == 'xsd') {
                $uris[] = $fileUriFactory->create($path);
            }
        }

        sort($uris);

        return $this->createFromUris($uris);
    }

    /**
     * @brief Schema that can be extended as needed
     *
     * Adding all XSDs to one schema may increase performance because common
     * components need ot be craeted only once.
     */
    public function getMainSchema(): Schema
    {
        return $this->createFromUris(
            [
                (new FileUriFactory())->create(
                    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                        . 'xsd' . DIRECTORY_SEPARATOR . 'xhtml-datatypes-1.xsd'
                )
            ]
        );
    }
}
