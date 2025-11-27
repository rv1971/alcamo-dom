<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class AttrTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getDataProvider
     */
    public function testGetData(
        $attr,
        $expectedText,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedXName,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri
    ): void {
        /* This also tests the traits HavingBaseUriTrait, HavingXNameTrait,
         * Rfc5147Trait. */

        $this->assertSame((string)$expectedText, (string)$attr);

        $this->assertSame((string)$expectedText, $attr->getValue());

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$attr->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$attr->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedXName,
            (string)$attr->getXName()
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $attr->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $attr->getRfc5147Uri()
        );
    }

    public function getDataProvider(): array
    {
        $fileUriFactory = new FileUriFactory(null, false);

        $fooDoc = Document::newFromUri(
            $fileUriFactory->create(self::DATA_DIR . 'foo.xml')
        );

        return [
            [
                $fooDoc['bar']->getAttributeNodeNS(Document::XML_NS, 'base'),
                'http://bar.example.biz',
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                Document::XML_NS . ' base',
                'line=14',
                $fooDoc->documentURI . '#line=14'
            ],
            [
                $fooDoc['bar']->firstChild->getAttributeNode('id'),
                'A',
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'id',
                'line=15',
                $fooDoc->documentURI . '#line=15'
            ]
        ];
    }
}
