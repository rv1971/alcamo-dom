<?php

namespace alcamo\dom\schema;

use alcamo\dom\{
    ConverterPool,
    Document,
    DocumentFactoryInterface,
    HavingDocumentFactoryInterface,
    HavingDocumentFactoryTrait,
    NamespaceConstantsInterface}
;
use alcamo\dom\decorated\Element as XsdElement;
use alcamo\dom\schema\component\TypeInterface;
use alcamo\uri\Uri;

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

    public const DEFAULT_DOCUMENT_FACTORY_CLASS =
        Schema::DEFAULT_DOCUMENT_FACTORY_CLASS;

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
        $cacheKey = SchemaCache::getInstance()->createKey($uris);

        $schema = SchemaCache::getInstance()[$cacheKey] ?? null;

        if (!isset($schema)) {
            $xsds = [];

            foreach ($uris as $uri) {
                $xsds[] = $this->documentFactory_->createFromUri($uri);
            }

            $class = static::SCHEMA_CLASS;

            $schema = new $class($xsds, $cacheKey);

            SchemaCache::getInstance()->add($schema);
        }

        return $schema;
    }

    /**
     * @brief Construct new schema or get it from cache
     *
     * @param $xsds alcamo::dom::Document objects containing XSDs to include
     * into the schema.
     */
    public function createFromXsds(array $xsds): Schema
    {
        $cacheKey = SchemaCache::getInstance()->createKey($xsds);

        $schema = SchemaCache::getInstance()[$cacheKey] ?? null;

        if (!isset($schema)) {
            $class = static::SCHEMA_CLASS;

            $schema = new $class($xsds, $cacheKey);

            SchemaCache::getInstance()->add($schema);
        }

        return $schema;
    }

    /// Create a type from an URI reference indicating an XSD element by ID
    public function createTypeFromUri($uri): TypeInterface
    {
        $xsdElement = $this->documentFactory_->createFromUri($uri);

        /** If no element with the ID given in the fragment part of $uri
         *  exists and the target namespace of the indicated document is the
         *  XSD namespace, take the corresponding XSD type. This allows to
         *  correctly create types like
         *  `http://www.w3.org/2001/XMLSchema#boolean` without actually
         *  accessing the document at http://www.w3.org/2001/XMLSchema. */
        if (!isset($xsdElement)) {
            $uri = $this->documentFactory_->resolveUri($uri);

            if (
                TargetNsCache::getInstance()[$uri->withFragment('')]
                    == self::XSD_NS
            ) {
                return $this->getBuiltinSchema()
                    ->getGlobalType(self::XSD_NS . ' ' . $uri->getFragment());

            }
        }

        return static::createTypeFromXsdElement($xsdElement);
    }

    /// Create type from a schema consisting of the element's owner document
    public function createTypeFromXsdElement(
        XsdElement $xsdElement
    ): TypeInterface {
        return $this->createFromXsds([ $xsdElement->ownerDocument ])
            ->getGlobalType($xsdElement->getComponentXName());
    }

    public function getBuiltinSchema(): Schema
    {
        return $this->createFromUris([]);
    }
}
