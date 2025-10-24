<?php

namespace alcamo\dom\decorated;

use alcamo\dom\DocumentFactoryInterface;
use alcamo\dom\extended\Document as BaseDocument;

/**
 * @namespace alcamo::dom::decorated
 *
 * @brief DOM classes providing element-specific element decorator objects
 */

/// Document element-specific decorators
class Document extends BaseDocument
{
    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    public const DEFAULT_DOCUMENT_FACTOTRY_CLASS = DocumentFactory::class;
}
