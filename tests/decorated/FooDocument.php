<?php

namespace alcamo\dom\decorated;

require_once 'FooBar.php';
require_once 'FooLiteral.php';

class FooDocument extends Document
{
    public const FOO_NS  = 'http://foo.example.org';
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    public const ELEMENT_DECORATOR_MAP = [
        self::FOO_NS . ' Bar'      => FooBar::class,
        self::RDFS_NS . ' Literal' => FooLiteral::class
    ];

    public const DEFAULT_DECORATOR_CLASS = FooShort::class;
}
