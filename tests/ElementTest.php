<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getMetaDataProvider
     */
    public function testGetMetaData(
        $element,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedXName,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri
    ): void {
        /* This also tests the traits HavingBaseUriTrait, HavingXNameTrait,
         * Rfc5147Trait. */

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$element->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$element->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedXName,
            (string)$element->getXName()
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $element->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $element->getRfc5147Uri()
        );
    }

    public function getMetaDataProvider(): array
    {
        $fileUriFactory = new FileUriFactory(null, false);

        $fooDoc = Document::newFromUrl(
            $fileUriFactory->create(self::DATA_DIR . 'foo.xml')
        );

        return [
            [
                $fooDoc->documentElement,
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'http://foo.example.org foo',
                'line=2',
                $fooDoc->documentURI . '#line=2'
            ],
            [
                $fooDoc['bar'],
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'https://bar.example.com bar',
                'line=7',
                $fooDoc->documentURI . '#line=7'
            ],
            [
                $fooDoc['bar']->firstChild,
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'https://bar.example.com baz',
                'line=8',
                $fooDoc->documentURI . '#line=8'
            ]
        ];
    }

    public function testGetIterator(): void
    {
        /* This also tests class ChildElementsIterator. */

        $fooDoc = Document::newFromUrl(
            (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml')
        );

        $data = [];

        foreach ($fooDoc['bar'] as $i => $text) {
            $data[$i] = (string)$text;
        }

        $this->assertSame(
            [
                0 => 'Lorem ipsum',
                1 => 'dolor sit amet',
                2 => 'consetetur sadipscing elitr'
            ],
            $data
        );
    }

    /**
     * @dataProvider getFirstSameAsProvider
     */
    public function getFirstSameAs($uri, $expectedText): void
    {
        $fooDoc = Document::newFromUrl(
            (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml')
        );

        $this->assertSame($expectedText, (string)$fooDoc->getFirstSameAs($uri));
    }

    public function getFirstSameAsProvider(): array
    {
        return [
            [ 'http://bar.example.biz#a', 'Lorem ipsum' ],
            [ 'http://bar.example.biz#b', 'dolor sit amet' ],
            [ 'http://baz.example.edu#s', 'sadipscing' ]
        ];
    }
}
