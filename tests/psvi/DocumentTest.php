<?php

namespace alcamo\dom\psvi;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\{Schema, TypeMap};
use alcamo\exception\DataValidationFailed;
use alcamo\xml\XName;

require_once 'FooDocument.php';

class DocumentTest extends TestCase
{
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    public static $doc;

    public static function setUpBeforeClass(): void
    {
        self::$doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/baz.xml'
        );
    }

    public function testGetSchema()
    {
        $this->assertInstanceOf(
            DocumentFactory::class,
            self::$doc->getDocumentFactory()
        );

        $this->assertInstanceOf(Schema::class, self::$doc->getSchema());

        $basenames = [];

        foreach (self::$doc->getSchema()->getXsds() as $url => $xsd) {
            $basenames[] = basename($url);
        }

        $this->assertSame([ 'XMLSchema.xsd', 'xml.xsd' ], $basenames);
    }

    /**
     * @dataProvider getAttrConvertersProvider
     */
    public function testGetAttrConverters(
        $nsName,
        $localName,
        $expectedConverter
    ) {
        $attrType = self::$doc->getSchema()
            ->getGlobalType(new XName($nsName, $localName));

        $converter = self::$doc->getAttrConverters()->lookup($attrType);

        if (isset($converter)) {
            $converter = explode('::', $converter)[1];
        }

        $this->assertSame($expectedConverter, $converter);
    }

    public function getAttrConvertersProvider()
    {
        return [
            [ self::XSD_NS, 'anyURI', 'toUri' ],
            [ self::XSD_NS, 'string', null ],
            [ self::XSD_NS, 'duration', 'toDuration' ],
            [ self::XSD_NS, 'unsignedByte', 'toInt' ]
        ];
    }

    public function testGetElementDecoratorMap()
    {
        $doc = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $this->assertInstanceOf(
            TypeMap::class,
            $doc->getElementDecoratorMap()
        );

        $this->assertSame(
            [ FooBar::class, FooLiteral::class ],
            array_values($doc->getElementDecoratorMap()->getMap())
        );
    }

    public function testValidateIdrefsIdref()
    {
        $doc = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-idref.xml'
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage('; no ID found for IDREF "z"');

        $doc->validateIdrefs();
    }

    public function testValidateIdrefsIdrefs()
    {
        $doc = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-idrefs.xml'
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage('; no ID found for IDREFS item "zz"');

        $doc->validateIdrefs();
    }

    public function testClone()
    {
        $doc1 = FooDocument::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        );

        $doc2 = clone $doc1;

        $this->assertFalse($doc1->isSameNode($doc2));

        $this->assertFalse(
            $doc1->documentElement->isSameNode($doc2->documentElement)
        );

        $this->assertFalse($doc2['a']->isSameNode($doc1['a']));

        $this->assertEquals(
            $doc1->documentElement->getLang(),
            $doc2->documentElement->getLang()
        );

        $this->assertNotSame(
            $doc1->documentElement->getLang(),
            $doc2->documentElement->getLang()
        );

        $this->assertSame(
            $doc1->getSchema(),
            $doc2->getSchema()
        );
    }
}
