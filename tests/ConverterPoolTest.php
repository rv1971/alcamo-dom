<?php

namespace alcamo\dom;

use alcamo\dom\psvi\Document as PsviDocument;
use alcamo\exception\{DataNotFound, OutOfRange, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{
    BooleanLiteral,
    DateLiteral,
    Lang,
    LangStringLiteral,
    Literal,
    MediaType,
    Node
};
use alcamo\time\Duration;
use alcamo\uri\{FileUriFactory, Uri};
use alcamo\xml\XName;
use Ds\Set;
use PHPUnit\Framework\TestCase;

class ConverterPoolTestDocument extends PsviDocument
{
    public const TYPE_CONVERTER_MAP =
        [
            Document::XSD_NS . ' formChoice'
                => __CLASS__ . '::formChoiceConverter',
            'http://foo.example.org UpperString'
                => __CLASS__ . '::upperConverter'
        ]
    + parent::TYPE_CONVERTER_MAP;

    public static function formChoiceConverter(string $value, \DOMNode $context)
    {
        return $context->parentNode->getAttribute('prefix') . $value;
    }

    public static function upperConverter(string $value, \DOMNode $context)
    {
        return strtoupper($value);
    }
}

class ConverterPoolTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    private static $doc_;

    public static function setUpBeforeClass(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
            0
        );

        self::$doc_ = $factory->createFromUri(
            'converter-data.xml',
            ConverterPoolTestDocument::class
        );
    }

    /**
     * @dataProvider conversionProvider
     */
    public function testConversion($id, $expectedResult): void
    {
        $element = self::$doc_[$id];

        $converter = explode('.', $id, 2)[0];

        $qualifiedConverter = ConverterPool::class . "::$converter";

        $node = $element->hasAttribute('value')
            ? $element->getAttributeNode('value')
            : $element;

        switch ($converter) {
            case 'toDateTime':
            case 'toDuration':
            case 'toIntSet':
            case 'toLang':
            case 'toLiteral':
            case 'toMediaType':
            case 'toNonNegativeRange':
            case 'toPrefixSet':
            case 'toRdfaNode':
            case 'toSet':
            case 'toUri':
            case 'toXName':
            case 'xhRelToUri':
            case 'xPointerUriToValueSet':
                $this->assertEquals(
                    $expectedResult,
                    $qualifiedConverter($node, $node)
                );
                break;

            case 'base64ToBinary':
            case 'hexToBinary':
            case 'curieToUri':
            case 'safeCurieToUri':
            case 'uriOrSafeCurieToUri':
            case 'xPointerUriToSubset':
                $this->assertSame(
                    $expectedResult,
                    (string)$qualifiedConverter($node, $node)
                );
                break;

            case 'toDocument':
                $this->assertSame(
                    $expectedResult,
                    (string)$qualifiedConverter($node, $node)
                        ->documentElement->localName
                );
                break;

            case 'toDocumentOrElement':
                $this->assertSame(
                    $expectedResult,
                    (string)$qualifiedConverter($node, $node)
                        ->localName
                );
                break;

            case 'resolveIdRef':
                $this->assertSame(
                    self::$doc_[$expectedResult],
                    $qualifiedConverter($node, $node)
                );
                break;

            default:
                $this->assertSame(
                    $expectedResult,
                    $qualifiedConverter($node, $node)
                );
        }
    }

    public function conversionProvider(): array
    {
        return [
            [ 'base64ToBinary', '666F6F0A' ],
            [ 'curieToUri', 'http://purl.org/dc/terms/source' ],
            [ 'hexToBinary', '1234ABCDEF' ],
            [ 'pairsToMap', [ 'foo' => 'bar', 'baz-qux' => '42' ] ],
            [ 'resolveIdRef', 'toLang' ],
            [ 'safeCurieToUri', 'http://qux.example.org#1234' ],
            [ 'toArray', [ 'foo', 'bar', 'baz' ] ],
            [ 'toBool.1', true ],
            [ 'toBool.2', false ],
            [ 'toDateTime', new \DateTime('2021-02-16T18:04:03.123+00:00') ],
            [ 'toDocument', 'foo' ],
            [ 'toDocumentOrElement', 'bar' ],
            [ 'toDuration', new Duration('PT5M') ],
            [ 'toFloat', 3.141 ],
            [ 'toInt', 42 ],
            [ 'toIntSet', new Set([ 42, -42, 0, 7, 5 ]) ],
            [ 'toLang', Lang::newFromPrimaryAndRegion('yo', 'NG') ],
            [ 'toLiteral.1', new Literal('Lorem ipsum') ],
            [ 'toLiteral.2', new LangStringLiteral('libertà', 'it') ],
            [ 'toLiteral.3', new DateLiteral('2026-01-30') ],
            [ 'toLiteral.4', new BooleanLiteral(true) ],
            [ 'toMediaType', new MediaType('application', 'json') ],
            [ 'toNonNegativeRange', new NonNegativeRange(42, 43) ],
            [ 'toRdfaNode.1', new Node('http://www.example.org') ],
            [
                'toRdfaNode.2',
                new Node(
                    'http://www.example.org/cn',
                    [
                        [ 'dc:language', 'cn' ],
                        [ 'dc:title', 'Cinese version' ],
                        [ 'dc:format', 'application/pdf' ]
                    ]
                )
            ],
            [ 'toSet', new Set(['foo', 'bar', 'baz']) ],
            [ 'toUri', new Uri('http://www.example.org/foo') ],
            [ 'toXName', new XName(Document::DC_NS, 'title') ],
            [ 'uriOrSafeCurieToUri.1', 'http://www.example.biz/foo' ],
            [ 'uriOrSafeCurieToUri.2', 'http://www.w3.org/2001/XMLSchema#token' ],
            [ 'xhRelToUri', new Uri(Document::XHV_NS . 'icon') ],
            [ 'xPointerUriToSubset', 'lorem-ipsum' ],
            [
                'xPointerUriToValueSet',
                (new Set())->merge(
                    [
                        'toDateTime',
                        'toDocument',
                        'toDocumentOrElement',
                        'toDuration'
                    ]
                )
            ],
            [ 'yesNoToBool.1', true ],
            [ 'yesNoToBool.2', false ]
        ];
    }

    /**
     * @dataProvider toRdfaDatatypeConversionProvider
     */

    /* This also tests class schema\Converter. */
    public function testToRdfaDatatypeConversion(
        $id,
        $expectedResult,
        $expectedClass
    ): void {
        $element = self::$doc_[$id];

        $qualifiedConverter = ConverterPool::class . "::toRdfaDatatype";

        $attrNode = $element->getAttributeNode('value');

        $value = $qualifiedConverter($attrNode, $attrNode);

        if (!isset($expectedClass)) {
            $this->assertSame($expectedResult, $value);
        } elseif (is_array($expectedClass)) {
            $this->assertIsArray($value);

            $this->assertSame(count($expectedResult), count($value));

            foreach ($expectedResult as $key => $item) {
                $this->assertInstanceOf($expectedClass[0], $value[$key]);
                $this->assertSame($item, (string)$value[$key]);
            }
        } else {
            $this->assertInstanceOf($expectedClass, $value);

            $this->assertSame($expectedResult, (string)$value);
        }
    }

    public function toRdfaDatatypeConversionProvider(): array
    {
        return [
            [ 'toRdfaDatatype.1', 'true', null ],
            [ 'toRdfaDatatype.2', true, null ],
            [ 'toRdfaDatatype.3', '**qualified', null ],
            [ 'toRdfaDatatype.4', 'LOREM IPSUM.', null ],
            [ 'toRdfaDatatype.5', 'restriction', Element::class ],
            [ 'toRdfaDatatype.6', [ 'foo', 'bar', 'baz' ], null ],
            [
                'toRdfaDatatype.7',
                [ 'BAZ' => 'BAZ', 'BAR' => 'BAR', 'FOO' => 'FOO' ],
                [ Element::class ]
            ],
            [
                'toRdfaDatatype.8',
                [
                    'bar:#top' => 'https://bar.example.com#top',
                    'rel:item' => 'rel#item'
                ],
                [ Uri::class ]
            ],
        ];
    }

    public function testToRdfaDatatypeFallback(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $doc = $factory->createFromUri('converter-data.xml');

        $qualifiedConverter = ConverterPool::class . '::toRdfaDatatype';

        $element2 = $doc->getElementById('toRdfaDatatype.2');

        $this->assertSame(
            true,
            $qualifiedConverter($element2->getAttribute('value'), $element2)
        );

        $element3 = $doc->getElementById('toRdfaDatatype.3');

        $this->assertSame(
            'qualified',
            $qualifiedConverter($element3->getAttribute('value'), $element3)
        );
    }

    public function testToIntException()
    {
        $this->expectException(OutOfRange::class);

        ConverterPool::toInt(PHP_INT_MAX . '0');
    }

    public function testXPointerUrlToSubsetException()
    {
        $this->expectException(SyntaxError::class);

        ConverterPool::xPointerUriToSubset(
            'foo.xml',
            self::$doc_->documentElement
        );
    }

    public function testToRdfaDatatypeException()
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $doc = $factory->createFromUri('converter-data.xml');

        $element = $doc->getElementById('toRdfaDatatype-invalid');

        $this->expectException(DataNotFound::class);

        $this->expectExceptionMessage(
            'type http://www.w3.org/2001/XMLSchema foo not found'
        );

        ConverterPool::toRdfaDatatype(
            $element->getAttribute('value'),
            $element
        );
    }
}
