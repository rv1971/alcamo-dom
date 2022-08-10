<?php

namespace alcamo\dom\decorated;

use alcamo\dom\{GetCommentInterface, GetLabelInterface};
use PHPUnit\Framework\TestCase;

class GetLabelAndCommentDecoratorTest extends TestCase
{
    /**
     * @dataProvider getLabelProvider
     */
    public function testGetLabel($elem, $lang, $fallbackFlags, $expectedLabel)
    {
        $this->assertEquals(
            $expectedLabel,
            $elem->getLabel($lang, $fallbackFlags)
        );
    }

    public function getLabelProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        $qux = $doc->documentElement->lastChild;

        return [
            [ $qux, null, null, null ],
            [ $qux, null, GetLabelInterface::FALLBACK_TO_NAME, 'qux' ],
            [ $qux, 'de', null, null ],
            [ $qux, 'it', GetLabelInterface::FALLBACK_TO_NAME, 'qux' ],
            [ $doc['a'], null, null, 'baz-a' ],
            [ $doc['a'], 'no', null, 'baz-a' ],
            [ $doc['x'], null, null, 'C oc' ],
            [ $doc['x'], 'oc', null, 'C oc' ],
            [ $doc['x'], 'sk', null, null ],
            [ $doc['x'], 'sk', GetLabelInterface::FALLBACK_TO_OTHER_LANG, 'C oc' ],
            [ $doc['x'], 'sk', GetLabelInterface::FALLBACK_TO_NAME, 'bar' ],
            [ $doc['corge'], null, null, 'CORGE' ],
            [ $doc['corge'], 'pl', null, 'CORGE-pl' ],
            [ $doc['corge'], 'pt', null, 'CORGE' ],
        ];
    }

    /**
     * @dataProvider getCommentProvider
     */
    public function testGetComment(
        $elem,
        $lang,
        $fallbackFlags,
        $expectedComment
    ) {
        $this->assertEquals(
            $expectedComment,
            $elem->getComment($lang, $fallbackFlags)
        );
    }

    public function getCommentProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            [ $doc->documentElement, null, null, 'Lorem ipsum' ],
            [ $doc->documentElement, 'oc', null, 'Lorem ipsum' ],
            [ $doc->documentElement, 'en', null, null ],
            [
                $doc->documentElement,
                'en',
                GetCommentInterface::FALLBACK_TO_OTHER_LANG,
                'Lorem ipsum'
            ]
        ];
    }
}
