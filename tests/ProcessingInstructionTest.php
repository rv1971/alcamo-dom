<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class ProcessingInstructionTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getDataProvider
     */
    public function testData(
        $piNode,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri,
        $expectedAttributes
    ): void {
        /* This also tests the traits HavingBaseUriTrait, Rfc5147Trait. */

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$piNode->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$piNode->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $piNode->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $piNode->getRfc5147Uri()
        );

        $this->assertFalse(isset($piNode->foO));

        $attrs = [];

        foreach ($piNode as $key => $value) {
            $attrs[$key] = (string)$value;

            $this->assertTrue(isset($piNode->$key));
            $this->assertSame((string)$value, $piNode->$key);
        }

        $this->assertSame($expectedAttributes, $attrs);
    }

    public function getDataProvider(): array
    {
        $fileUriFactory = new FileUriFactory(null, false);

        $fooDoc = Document::newFromUri(
            $fileUriFactory->create(self::DATA_DIR . 'foo.xml')
        );

        return [
            [
                $fooDoc->firstChild->nextSibling,
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'line=5',
                $fooDoc->documentURI . '#line=5',
                [ 'foo' => 'FOO', 'bar' => 'BAR' ]
            ],
            [
                $fooDoc->firstChild->nextSibling->nextSibling,
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'line=7',
                $fooDoc->documentURI . '#line=7',
                [
                    'href' => 'foo.xsl',
                    'type' => 'text/xsl'
                ]
            ]
        ];
    }
}
