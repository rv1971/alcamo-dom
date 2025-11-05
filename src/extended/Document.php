<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Document as BaseDocument, DocumentFactoryInterface};

/**
 * @namespace alcamo::dom::extended
 *
 * @brief Access to attributes as magic properties with value conversion
 *
 * The derived classes for attribute and element nodes defined in
 * this namespace add properties. See RegisteredNodeTrait for an explanation
 * why and how these properties need to be conserved.
 *
 * To clone DOM nodes other than DOM documents, the right thing to do is using
 * DOMNode::cloneNode() rather than PHP's `clone` mechanism.
 * DOMNode::cloneNode() creates a new PHP object from a new DOM object, so the
 * clone does not have any of the added properties of the original PHP
 * object. Therefore, no __clone() methods are defined in any class derived in any way from
 * DOMNode, except those classes derived from DOMDocument.
 *
 * Cloning a document with PHP's `clone` mechanism may be useful instead
 * becasue PHP then creates a deep clone of the underlying DOM tree, hence
 * __clone() methods are defined on document level instead.
 */

/**
 * @brief DOM Document class having elements and attributes with extended
 * features
 *
 * @date Last reviewed 2025-11-05
 */
class Document extends BaseDocument
{
    use NodeRegistryTrait;

    /** @copybrief alcamo::dom::Document::NODE_CLASSES */
    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /** @copybrief alcamo::dom::Document::DEFAULT_DOCUMENT_FACTOTRY_CLASS */
    public const DEFAULT_DOCUMENT_FACTOTRY_CLASS = DocumentFactory::class;

    public function clearCache(): void
    {
        parent::clearCache();

        $this->clearNodeRegistry();
    }
}
