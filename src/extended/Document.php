<?php

namespace alcamo\dom\extended;

use alcamo\dom\{Document as BaseDocument, DocumentFactoryInterface};

class Document extends BaseDocument
{
    use NodeRegistryTrait;

    public const NODE_CLASSES =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASSES;

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }
}
