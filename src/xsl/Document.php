<?php

namespace alcamo\dom\xsl;

use alcamo\dom\extended\Document as BaseDocument;

class Document extends BaseDocument
{
    public const NS = parent::NS + [
        'xsl' => 'http://www.w3.org/1999/XSL/Transform'
    ];

    public const NODE_CLASS =
        [
            'DOMAttr' => Attr::class,
        ]
        + parent::NODE_CLASS;
}
