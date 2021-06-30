<?php

namespace alcamo\dom\xsl;

use alcamo\dom\extended\Document as BaseDocument;

class Document extends BaseDocument
{
    public const NSS = parent::NSS + [
        'xsl' => 'http://www.w3.org/1999/XSL/Transform'
    ];

    public const NODE_CLASSES =
        [
            'DOMAttr' => Attr::class,
        ]
        + parent::NODE_CLASSES;
}
