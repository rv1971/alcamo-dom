<?php

namespace alcamo\dom\xsd;

use alcamo\dom\{DocumentFactoryInterface, ValidationTrait};
use alcamo\dom\extended\{Document as BaseDocument, DocumentFactory};

class Document extends BaseDocument
{
    use ValidationTrait;

    public const NODE_CLASS =
        [
            'DOMAttr'    => Attr::class,
            'DOMElement' => Element::class
        ]
        + parent::NODE_CLASS;

    public function getDocumentFactory(): DocumentFactoryInterface
    {
        return new DocumentFactory();
    }
}
