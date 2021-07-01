<?php

namespace alcamo\dom\extended;

use alcamo\dom\DocumentFactory as DocumentFactoryBase;

/**
 * @brief Factory for DOM documents
 *
 * Unlike its parent, by default this creates Document objects rather than
 * alcamo::dom::Document objects.
 *
 * @date Last reviewed 2021-07-01
 */
class DocumentFactory extends DocumentFactoryBase
{
    /// @copybrief alcamo::dom::DocumentFactory::DEFAULT_CLASS
    public const DEFAULT_CLASS = Document::class;
}
