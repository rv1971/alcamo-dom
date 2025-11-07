<?php

namespace alcamo\dom\decorated;

use alcamo\dom\DocumentFactoryInterface;
use alcamo\dom\extended\Document as BaseDocument;

/**
 * @namespace alcamo::dom::decorated
 *
 * @brief DOM classes providing element-specific element decorator objects
 */

/**
 * @brief Document with element-specific decorators
 *
 * @date Last reviewed 2025-11-05
 */
class Document extends BaseDocument
{
    /** @copybrief alcamo::dom::Document::NODE_CLASSES */
    public const NODE_CLASSES =
        [
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /** @copybrief alcamo::dom::Document::DEFAULT_DOCUMENT_FACTORY_CLASS */
    public const DEFAULT_DOCUMENT_FACTORY_CLASS = DocumentFactory::class;
}
