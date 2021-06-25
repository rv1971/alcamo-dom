<?php

namespace alcamo\dom\decorated;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\TypeMap;
use alcamo\exception\MethodNotFound;
use alcamo\xml\XName;

require_once 'FooDocument.php';

class ElementTest extends TestCase
{
    public const FOO_NS  = 'http://foo.example.org';
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    public function testDecoration()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->assertSame("Hello, I'm x!", $doc['x']->hello());

        $this->assertSame($doc['x'], $doc['x']->getElement());

        $this->assertSame(
            'Hello! Lorem ipsum',
            $doc->documentElement->firstChild->hello()
        );
    }

    public function testException()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->expectException(MethodNotFound::class);
        $this->expectExceptionMessage(
            'Method "bar" not found in ' . FooBar::class
        );

        $doc['x']->bar();
    }
}
