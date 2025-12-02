<?php

namespace alcamo\dom;

use alcamo\dom\psvi\Document as PsviDocument;
use alcamo\exception\{OutOfRange, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{Lang, MediaType};
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
                => __CLASS__ . '::formChoiceConverter'
        ]
    + parent::TYPE_CONVERTER_MAP;

    public static function formChoiceConverter(string $value, \DOMNode $context)
    {
        return $context->parentNode->getAttribute('prefix') . $value;
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

        $attrNode = $element->getAttributeNode('value');

        switch ($converter) {
            case 'toDateTime':
            case 'toDuration':
            case 'toIntSet':
            case 'toLang':
            case 'toMediaType':
            case 'toNonNegativeRange':
            case 'toPrefixSet':
            case 'toSet':
            case 'toUri':
            case 'toXName':
            case 'xhRelToUri':
            case 'xPointerUriToValueSet':
                $this->assertEquals(
                    $expectedResult,
                    $qualifiedConverter($attrNode, $attrNode)
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
                    (string)$qualifiedConverter($attrNode, $attrNode)
                );
                break;

            case 'toDocument':
                $this->assertSame(
                    $expectedResult,
                    (string)$qualifiedConverter($attrNode, $attrNode)
                        ->documentElement->localName
                );
                break;

            case 'resolveIdRef':
                $this->assertSame(
                    self::$doc_[$expectedResult],
                    $qualifiedConverter($attrNode, $attrNode)
                );
                break;

            default:
                $this->assertSame(
                    $expectedResult,
                    $qualifiedConverter($attrNode, $attrNode)
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
            [ 'toDuration', new Duration('PT5M') ],
            [ 'toFloat', 3.141 ],
            [ 'toInt', 42 ],
            [ 'toIntSet', new Set([ 42, -42, 0, 7, 5 ]) ],
            [ 'toLang', Lang::newFromPrimaryAndRegion('yo', 'NG') ],
            [ 'toMediaType', new MediaType('application', 'json') ],
            [ 'toNonNegativeRange', new NonNegativeRange(42, 43) ],
            [ 'toSet', new Set(['foo', 'bar', 'baz']) ],
            [ 'toUri', new Uri('http://www.example.org/foo') ],
            [ 'toXName', new XName(Document::DC_NS, 'title') ],
            [ 'uriOrSafeCurieToUri.1', 'http://www.example.biz/foo' ],
            [ 'uriOrSafeCurieToUri.2', 'http://www.w3.org/2001/XMLSchema#token' ],
            [ 'xhRelToUri', new Uri(Document::XHV_NS . 'icon') ],
            [ 'xPointerUriToSubset', 'lorem-ipsum' ],
            [
                'xPointerUriToValueSet',
                (new Set())->merge([ 'toDateTime', 'toDocument', 'toDuration' ])
            ],
            [ 'yesNoToBool.1', true ],
            [ 'yesNoToBool.2', false ],
            [ 'toRdfaDatatype.1', 'true' ],
            [ 'toRdfaDatatype.2', 'true' ],
            [ 'toRdfaDatatype.3', true ],
            [ 'toRdfaDatatype.4', '**qualified' ]
        ];
    }

    public function testToRdfaDatatypeFallback(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $doc = $factory->createFromUri('converter-data.xml');

        $qualifiedConverter = ConverterPool::class . '::toRdfaDatatype';

        $element3 = $doc->getElementById('toRdfaDatatype.3');

        $this->assertSame(
            true,
            $qualifiedConverter($element3->getAttribute('value'), $element3)
        );

        $element4 = $doc->getElementById('toRdfaDatatype.4');

        $this->assertSame(
            'qualified',
            $qualifiedConverter($element4->getAttribute('value'), $element4)
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
}
