<?php

namespace alcamo\dom\xsd;

use PHPUnit\Framework\TestCase;
use alcamo\dom\GetLabelInterface;
use alcamo\xml\XName;

class ElementTest extends TestCase
{
    /**
     * @dataProvider getComponentXNameProvider
     */
    public function testGetComponentXName($elem, $expectedName)
    {
        $this->assertEquals($expectedName, $elem->getComponentXName());
    }

    public function getComponentXNameProvider()
    {
        $doc = Document::newFromUrl(
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
        )->conserve();

        return [
            'type' => [
                $doc->query('//*[@name = "openAttrs"]')[0],
                new XName(Document::XSD_NS, 'openAttrs')
            ],
            'annotation' => [
                $doc->query('//*[@name = "openAttrs"]/*')[0],
                null
            ],
            'attribute' => [
                $doc->query('//*[@name = "id"]')[0],
                new XName(null, 'id')
            ]
        ];
    }

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
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xsd'
        )->conserve();

        return [
            [ $doc['foo'], null, null, 'language-agnostic foo' ],
            [ $doc['foo'], 'en', null, 'English foo' ],
            [ $doc['foo'], 'de', null, 'deutsches foo' ],
            [ $doc['foo'], 'fr', null, 'foo français' ],
            [ $doc['foo'], 'la', null, 'language-agnostic foo' ],
            [ $doc['bar'], null, null, 'bar italiano' ],
            [ $doc['bar'], 'en', null, 'English bar' ],
            [ $doc['bar'], 'fr', null, null ],
            [
                $doc['bar'],
                'es',
                GetLabelInterface::FALLBACK_TO_OTHER_LANG,
                'bar italiano'
            ],
            [
                $doc['bar'],
                'es',
                GetLabelInterface::FALLBACK_TO_OTHER_LANG
                | GetLabelInterface::FALLBACK_TO_NAME,
                'bar italiano'
            ],
            [
                $doc['bar'],
                'es',
                GetLabelInterface::FALLBACK_TO_NAME,
                'bar'
            ]
        ];
    }
}
