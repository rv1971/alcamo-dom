<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\DocumentFactory as DocumentFactoryBase;

/**
 * @brief Factory for DOM documents
 *
 * Unlike its parent, by default this creates Document objects rather than
 * alcamo::dom::extended::Document objects.
 *
 * @date Last reviewed 2021-07-11
 */
class DocumentFactory extends DocumentFactoryBase
{
    public const DEFAULT_CLASS = Document::class;
}
