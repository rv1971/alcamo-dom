<?php

namespace alcamo\dom\decorated;

use alcamo\dom\extended\DocumentFactory as DocumentFactoryBase;

/**
 * @brief Factory for DOM documents
 *
 * Unlike its parent, by default this creates Document objects rather than
 * alcamo::dom::extended::Document objects.
 *
 * @date Last reviewed 2025-10-23
 */
class DocumentFactory extends DocumentFactoryBase
{
    public const DEFAULT_DOCUMENT_CLASS = Document::class;
}
