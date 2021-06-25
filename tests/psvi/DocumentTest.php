<?php

namespace alcamo\dom\psvi;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\Schema;
use alcamo\exception\DataValidationFailed;
use alcamo\xml\XName;

class DocumentTest extends TestCase
{
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    public static $doc;

    public static function setUpBeforeClass(): void
    {
        self::$doc = Document::newFromUrl(
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

    public function testValidateIdrefsIdref()
    {
        $doc = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-idref.xml'
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            "Failed to validate \"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
            . "<...\" at " . $doc->documentURI
            . ", line 8; no ID found for IDREF \"z\""
        );

        $doc->validateIdrefs();
    }

    public function testValidateIdrefsIdrefs()
    {
        $doc = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-idrefs.xml'
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            "Failed to validate \"<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
            . "<...\" at " . $doc->documentURI
            . ", line 9; no ID found for IDREF \"zz\""
        );

        $doc->validateIdrefs();
    }
}
