<?php

namespace alcamo\dom\xsl;

use alcamo\dom\extended\Document as BaseDocument;

/**
 * @namespace alcamo::dom::xsl
 *
 * @brief Specialized DOM classes for XSL documents
 */

/**
 * @brief DOM class for XSL documents with a specialized attribute class
 *
 * @date Last reviewed 2025-11-05
 */
class Document extends BaseDocument
{
    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMAttr' => Attr::class,
        ]
        + parent::NODE_CLASSES;
}
