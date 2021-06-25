<?php

namespace alcamo\dom\decorated;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\TypeMap;
use alcamo\xml\XName;

require 'FooDocument.php';

class DocumentTest extends TestCase
{
    public const FOO_NS  = 'http://foo.example.org';
    public const RDFS_NS = 'http://www.w3.org/2000/01/rdf-schema#';

    public function testGetElementDecoratorMap()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->assertInstanceOf(
            DocumentFactory::class,
            $doc->getDocumentFactory()
        );

        $this->assertInstanceOf(TypeMap::class, $doc->getElementDecoratorMap());

        $this->assertSame(
            [ FooBar::class, FooLiteral::class ],
            array_values($doc->getElementDecoratorMap()->getMap())
        );

        $this->assertSame(
            FooShort::class,
            $doc->getElementDecoratorMap()->getDefaultValue()
        );
    }
}
