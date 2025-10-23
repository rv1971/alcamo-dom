<?php

namespace alcamo\dom\xsd;

use PHPUnit\Framework\TestCase;
use alcamo\dom\HavingDocumentationInterface;
use alcamo\dom\decorated\Document;
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
                HavingDocumentationInterface::FALLBACK_TO_OTHER_LANG,
                'bar italiano'
            ],
            [
                $doc['bar'],
                'es',
                HavingDocumentationInterface::FALLBACK_TO_OTHER_LANG
                | HavingDocumentationInterface::FALLBACK_TO_NAME,
                'bar italiano'
            ],
            [
                $doc['bar'],
                'es',
                HavingDocumentationInterface::FALLBACK_TO_NAME,
                'bar'
            ]
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
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xsd'
        )->conserve();

        return [
            [ $doc['foo'], null, null, 'language-agnostic description' ],
            [ $doc['foo'], 'en', null, 'Description' ],
            [ $doc['foo'], 'es', null, 'Descripción' ],
            [ $doc['foo'], 'de', null, 'language-agnostic description' ],
            [ $doc['bar'], null, null, 'Descrizione' ],
            [ $doc['bar'], 'de', null, 'Beschreibung' ],
            [ $doc['bar'], 'fr', null, null ],
            [
                $doc['bar'],
                'es',
                HavingDocumentationInterface::FALLBACK_TO_OTHER_LANG,
                'Descrizione'
            ]
        ];
    }
}
