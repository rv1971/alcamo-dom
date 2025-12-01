<?php

namespace alcamo\dom\decorated;

use alcamo\dom\decorated\HavingDocumentationDecorator as HDD;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class HavingDocumentationDecoratorTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    private static $factory_;
    private static $doc_;

    public static function setUpBeforeClass(): void
    {
        self::$factory_ =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        self::$doc_ = self::$factory_->createFromUri('foo.xml', null, false);
    }

    /**
     * @dataProvider basicsProvider
     */
    public function testBasics($pos, $expectedText): void
    {
        $elements = self::$doc_->documentElement->childNodes;

        $this->assertSame(
            $elements->item($pos),
            $elements->item($pos)->getDecorator()->getElement()
        );

        $this->assertSame(
            $expectedText,
            (string)$elements->item($pos)->firstChild
        );
    }

    public function basicsProvider(): array
    {
        return [
            [ 1, 'Lorem ipsum.' ],
            [ 2, 'Lorem ipsum dolor sit amet.' ]
        ];
    }

    /**
     * @dataProvider getLabelProvider
     */
    public function testGetLabel(
        $pos,
        $lang,
        $fallbackFlags,
        $expectedLabel
    ): void {
        $elements = self::$doc_->documentElement->childNodes;

        $this->assertSame(
            $expectedLabel,
            $elements->item($pos)->getLabel($lang, $fallbackFlags)
        );
    }

    public function getLabelProvider(): array
    {
        return [
            [ 0, null, HDD::FALLBACK_TO_OTHER_LANG, null ],
            [ 0, null, HDD::FALLBACK_TO_NAME, 'bar' ],
            [
                0,
                null,
                HDD::FALLBACK_TO_SAME_AS_FRAGMENT | HDD::FALLBACK_TO_NAME,
                'short'
            ],
            [ 1, null, null, 'Very short text' ],
            [ 1, 'en', null, 'Very short text' ],
            [ 1, 'fr', null, 'Very short text' ],
            [ 2, 'ee', null, null ],
            [
                2,
                'ee',
                HDD::FALLBACK_TO_OTHER_LANG
                    | HDD::FALLBACK_TO_SAME_AS_FRAGMENT
                    | HDD::FALLBACK_TO_NAME,
                'Text bref'
            ],
            [ 2, 'en', null, 'Short text' ],
            [ 2, 'fr', null, 'Text bref' ]
        ];
    }

    /**
     * @dataProvider getCommentProvider
     */
    public function testGetComment(
        $pos,
        $lang,
        $fallbackFlags,
        $expectedComment
    ): void {
        $elements = self::$doc_->documentElement->childNodes;

        $this->assertSame(
            $expectedComment,
            $elements->item($pos)->getComment($lang, $fallbackFlags)
        );
    }

    public function getCommentProvider(): array
    {
        return [
            [ 0, null, null, 'empty' ],
            [ 0, 'is', null, 'empty' ],
            [ 0, 'yo', null, 'empty' ],
            [ 1, null, null, 'Questo testo è piuttosto breve.' ],
            [ 1, 'en', null, 'This is a quite short text.' ],
            [ 1, 'it', null, 'Questo testo è piuttosto breve.' ],
            [ 1, 'es', null, null ],
            [
                1,
                'es',
                HDD::FALLBACK_TO_OTHER_LANG,
                'Questo testo è piuttosto breve.'
            ]
        ];
    }
}
