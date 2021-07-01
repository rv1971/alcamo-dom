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
 */

/**
 * @brief DOM Document class having elements and attributes with extended
 * features.
 *
 * @date Last reviewed 2021-07-01
 */
class Document extends BaseDocument
{
    use NodeRegistryTrait;

    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /**
     * @copybrief alcamo::dom::Document::getDocumentFactory()
     *
     * Unlike its parent, this returns a DocumentFactory object rather than an
     * alcamo::dom::DocumentFactory object.
     */
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }
}
