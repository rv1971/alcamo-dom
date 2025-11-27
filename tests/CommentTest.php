<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getDataProvider
     */
    public function testData(
        $commentNode,
        $expectedComment,
        $expectedBaseUri,
        $expectedResolvedUri,
        $expectedRfc5147Fragment,
        $expectedRfc5147Uri
    ): void {
        /* This also tests the traits HavingBaseUriTrait, HavingXNameTrait,
         * Rfc5147Trait. */

        $this->assertSame((string)$expectedComment, (string)$commentNode);

        $this->assertSame(
            (string)$expectedBaseUri,
            (string)$commentNode->getBaseUri()
        );

        $this->assertSame(
            (string)$expectedResolvedUri,
            (string)$commentNode->resolveUri('README.md')
        );

        $this->assertSame(
            $expectedRfc5147Fragment,
            $commentNode->getRfc5147Fragment()
        );

        $this->assertSame(
            $expectedRfc5147Uri,
            $commentNode->getRfc5147Uri()
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
                $fooDoc->firstChild,
                " initial\nmulti-line comment ",
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'line=3',
                $fooDoc->documentURI . '#line=3'
            ],
            [
                $fooDoc['bar']->nextSibling,
                ' final comment ',
                $fooDoc->documentURI,
                $fileUriFactory->create(self::DATA_DIR . 'README.md'),
                'line=23',
                $fooDoc->documentURI . '#line=23'
            ]
        ];
    }
}
