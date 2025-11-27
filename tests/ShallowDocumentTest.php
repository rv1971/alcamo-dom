<?php

namespace alcamo\dom;

use alcamo\exception\{FileLoadFailed, SyntaxError};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class MyShallowDocument extends ShallowDocument
{
    public const MAX_LENGH = 32;
}

class ShallowDocumentTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public const XSD_DIR = self::DATA_DIR
        . '..' . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider loadUriProvider
     */
    public function testLoadUri($uri, $expectedXName, $expectedLang): void
    {
        $fileUriFactory = new FileUriFactory(null, false);

        $doc = ShallowDocument::newFromUri(
            $fileUriFactory->create($uri)
        );

        $this->assertNull($doc->documentElement->firstChild);

        $this->assertSame(
            $expectedXName,
            (string)$doc->documentElement->getXName()
        );

        $this->assertSame(
            $expectedLang,
            (string)$doc->documentElement
                ->getAttributeNS(Document::XML_NS, 'lang')
        );
    }

    public function loadUriProvider(): array
    {
        return [
            [
                self::DATA_DIR . 'foo.xml',
                'http://foo.example.org foo',
                ''
            ],
            [
                self::XSD_DIR . 'xml.xsd',
                'http://www.w3.org/2001/XMLSchema schema',
                'en'
            ],
            [
                self::XSD_DIR . 'XMLSchema.xsd',
                'http://www.w3.org/2001/XMLSchema schema',
                'EN'
            ],
        ];
    }

    public function testLoadUriException(): void
    {
        $this->expectException(FileLoadFailed::class);
        $this->expectExceptionMessage('Failed to load "none.xml"');

        ShallowDocument::newFromUri('none.xml');
    }

    public function testLoadXmlException(): void
    {
        $illFormedUri =
            (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            'Syntax error in "<?xml version=\'1.0\'?>\n\n<!-- init"; '
                . 'no complete opening tag found'
        );

        MyShallowDocument::newFromUri($illFormedUri);
    }
}
