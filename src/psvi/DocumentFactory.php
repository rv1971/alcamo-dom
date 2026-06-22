<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\DocumentFactory as BaseDocumentFactory;

/**
 * @brief Factory for DOM documents
 *
 * Unlike its parent, by default this creates alcamo::dom::psvi::Document
 * objects rather than alcamo::dom::decorated::Document objects.
 *
 * @date Last reviewed 2025-11-25
 */
class DocumentFactory extends BaseDocumentFactory
{
    public const DEFAULT_DOCUMENT_CLASS = Document::class;
}
