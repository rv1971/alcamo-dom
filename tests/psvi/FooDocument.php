<?php

namespace alcamo\dom\psvi;

use alcamo\dom\decorated\AbstractElementDecorator;

require "FooBar.php";
require "FooLiteral.php";
require "FooShort.php";
require "FooElement.php";

class FooDocument extends Document
{
    public const FOO_NS  = 'http://foo.example.org';
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    public const NODE_CLASSES =
        [
            'DOMElement' => FooElement::class
        ]
        + parent::NODE_CLASSES;

    public const ELEMENT_DECORATOR_MAP = [
        self::FOO_NS  . ' Bar'     => FooBar::class,
        self::RDFS_NS . ' Literal' => FooLiteral::class
    ];
}
