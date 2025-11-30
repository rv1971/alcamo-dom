<?php

namespace alcamo\dom;

/**
 * @brief Class that provides a document factory
 *
 * @date Last reviewed 2025-10-23
 */
interface HavingDocumentFactoryInterface
{
    /// Default class for a new document factory
    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;

    public function getDocumentFactory(): DocumentFactoryInterface;
}
