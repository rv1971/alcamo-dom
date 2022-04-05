<?php

namespace alcamo\dom\psvi;

use alcamo\dom\GetLabelInterface;
use PHPUnit\Framework\TestCase;

class GetLabelDecoratorTest extends TestCase
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
            [ $qux, null, GetLabelInterface::FALLBACK_TO_TYPE_NAME, 'short' ],
            [
                $qux,
                null,
                GetLabelInterface::FALLBACK_TO_TYPE_NAME
                | GetLabelInterface::FALLBACK_TO_NAME,
                'short'
            ],
            [ $qux, null, GetLabelInterface::FALLBACK_TO_NAME, 'qux' ],
            [ $qux, 'de', null, null ],
            [ $qux, 'fr', GetLabelInterface::FALLBACK_TO_TYPE_NAME, 'short' ],
            [
                $qux,
                'en',
                GetLabelInterface::FALLBACK_TO_TYPE_NAME
                | GetLabelInterface::FALLBACK_TO_NAME,
                'short'
            ],
            [ $qux, 'it', GetLabelInterface::FALLBACK_TO_NAME, 'qux' ],
            [ $doc['a'], null, null, 'baz-a' ],
            [ $doc['a'], 'no', null, 'baz-a' ],
            [ $doc['x'], null, null, 'C oc' ],
            [ $doc['x'], 'oc', null, 'C oc' ],
            [ $doc['x'], 'sk', null, null ],
            [ $doc['x'], 'sk', GetLabelInterface::FALLBACK_TO_OTHER_LANG, 'C oc' ],
            [ $doc['x'], 'sk', GetLabelInterface::FALLBACK_TO_TYPE_NAME, 'Bar' ],
            [ $doc['x'], 'sk', GetLabelInterface::FALLBACK_TO_NAME, 'bar' ],
            [ $doc['corge'], null, null, 'CORGE' ],
            [ $doc['corge'], 'pl', null, 'CORGE-pl' ],
            [ $doc['corge'], 'pt', null, 'CORGE' ],
        ];
    }
}
