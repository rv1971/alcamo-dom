<?php

namespace alcamo\dom\extended;

use alcamo\dom\DocumentFactory as BaseDocumentFactory;

/**
 * @brief Factory for DOM documents
 *
 * Unlike its parent, by default this creates alcamo::dom::extended::Document
 * objects rather than alcamo::dom::Document objects.
 *
 * @date Last reviewed 2025-11-05
 */
class DocumentFactory extends BaseDocumentFactory
{
    /// @copybrief alcamo::dom::DocumentFactory::DEFAULT_DOCUMENT_CLASS
    public const DEFAULT_DOCUMENT_CLASS = Document::class;
}
