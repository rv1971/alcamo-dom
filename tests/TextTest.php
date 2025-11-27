<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getDataProvider
     */
    public function testData(
        $textNode,
        $expectedText,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri
    ): void {
        /* This also tests the traits HavingBaseUriTrait, Rfc5147Trait. */

        $this->assertSame((string)$expectedText, (string)$textNode);

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$textNode->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$textNode->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $textNode->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $textNode->getRfc5147Uri()
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
                $fooDoc['bar']->firstChild->firstChild,
                'Lorem ipsum',
                'http://bar.example.biz',
                'http://bar.example.biz/README.md',
                'line=15',
                $fooDoc->documentURI . '#line=15'
            ],
            [
                $fooDoc['bar']->query('*[3]/xh:b/text()')[0],
                'sadipscing',
                'http://baz.example.edu',
                'http://baz.example.edu/README.md',
                'line=20',
                $fooDoc->documentURI . '#line=20'
            ]
        ];
    }
}
