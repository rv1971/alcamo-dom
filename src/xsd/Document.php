<?php

namespace alcamo\dom\xsd;

use alcamo\dom\{DocumentFactoryInterface, ValidationTrait};
use alcamo\dom\extended\{Document as BaseDocument, DocumentFactory};

/**
 * @namespace alcamo::dom::xsd
 *
 * @brief Specialized DOM classes for XSDs
 */

/**
 * @brief DOM class for XSDs with specialized nodes classes
 *
 * @date Last reviewed 2021-07-09
 */
class Document extends BaseDocument
{
    use ValidationTrait;

    /// @copybrief alcamo::dom::Document::NODE_CLASSES
    public const NODE_CLASSES =
        [
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    /// @copybrief alcamo::dom::Document::getDocumentFactory()
    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }
}
