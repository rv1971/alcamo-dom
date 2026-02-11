<?php

namespace alcamo\dom\decorated;

use alcamo\rdfa\{
    BooleanLiteral,
    LangStringLiteral,
    RdfaData
};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class HavingRdfaDataDecoratorTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    private static $doc_;

    public static function setUpBeforeClass(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
            0
        );

        self::$doc_ = $factory->createFromUri('rdfa-data.xml');
    }

    /**
     * @dataProvider getRdfaDataProvider
     */
    public function testGetRdfaData($id, $expectedData): void
    {
        $this->assertEquals(
            RdfaData::newFromIterable($expectedData),
            self::$doc_[$id]->getRdfaData()
        );
    }

    public function getRdfaDataProvider(): array
    {
        return [
            [
                'root',
                [
                    [ 'dc:title', new LangStringLiteral('My title', 'en') ],
                    [ 'owl:versionInfo', '1.42' ],
                    [
                        'rdfs:comment',
                        new LangStringLiteral(
                            'This is an English-language document.',
                            'en'
                        )
                    ],
                    [
                        'rdfs:comment',
                        new LangStringLiteral(
                            'Ceci est un document en anglais.',
                            'fr'
                        )
                    ],
                    [
                        'dc:creator',
                        new LangStringLiteral('Bob', 'en')
                    ],
                    [ 'rdfs:seeAlso', 'http://www.example.com/bob/mydoc' ],
                    [ 'dc:bazzable', new BooleanLiteral(true) ],
                    [ 'dc:created', '2026-02-10' ],
                    [ 'dc:modified', '2026-02-10' ],
                    [
                        'dc:alternative',
                        new LangStringLiteral('Mein Titel', 'de')
                    ],
                    [
                        'dc:source',
                        [
                            'https://www.example.biz/baz',
                            [
                                [ 'dc:title', 'Original data' ],
                                [ 'dc:format', 'application/json' ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
