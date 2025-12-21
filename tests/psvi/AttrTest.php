<?php

namespace alcamo\dom\psvi;

use alcamo\dom\xsd\Decorator as XsdDecorator;
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class MyDocument2 extends Document
{
    public const BAR_NS = 'https://bar.example.com';

    public const CORGE_NS = 'https://corge.example.edu';

    public const TYPE_CONVERTER_MAP =
        [
            self::BAR_NS . ' Fooable' => __CLASS__ . '::toUpper',
            self::BAR_NS . ' BarId' => __CLASS__ . '::toBarId'
        ]
    + parent::TYPE_CONVERTER_MAP;

    public static function toUpper(string $value): \stdClass
    {
        return (object)[ strtoupper($value) ];
    }

    public static function toBarId(string $value): string
    {
        return "_{$value}_";
    }
}

class AttrTest extends TestCase
{
    public const BAR_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'schema' . DIRECTORY_SEPARATOR
        . 'bar.xml';

    public const BAR_NS = MyDocument2::BAR_NS;

    public const FOO_NS = 'http://foo.example.org';

    /**
     * @dataProvider propsProvider
     */
    public function testProps(
        $xPath,
        $expectedAttrTypeXName,
        $expectedValueType,
        $expectedValue
    ): void {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())->create(self::BAR_PATH),
            MyDocument2::class,
            false
        );

        $attr = $doc->query($xPath)[0];

        $this->assertInstanceOf(Attr::class, $attr);

        $this->assertEquals(
            $expectedAttrTypeXName,
            $attr->getType()->getXName()
        );

        if (strpos($expectedValueType, '\\') === false) {
            $this->assertSame($expectedValueType, gettype($attr->getValue()));

            $this->assertSame($expectedValue, $attr->getValue());
        } else {
            $value = $attr->getValue();

            $this->assertSame($value, $attr->getValue());

            $this->assertInstanceOf($expectedValueType, $value);

            $this->assertEquals($expectedValue, $value);
        }
    }

    public function propsProvider(): array
    {
        return [
            [
                '*//*[local-name() = "corge"]/@xsi:type',
                new XName(Document::XSD_NS, 'QName'),
                XName::class,
                new XName(MyDocument2::CORGE_NS, 'unknown')
            ],
            [
                '*//*[local-name() = "corge"]/@barable',
                new XName(Document::XSD_NS, 'anySimpleType'),
                'string',
                'unclear'
            ],
            [
                '*//*[local-name() = "corge"]/@*[local-name() = "id"][2]',
                new XName(self::BAR_NS, 'BarId'),
                'string',
                '_Corge_'
            ],
            [
                '*//*[local-name() = "corge"]/@*[local-name() = "id"][3]',
                new XName(Document::XSD_NS, 'anySimpleType'),
                'string',
                'CORGE'
            ],
            [
                '*//*[local-name() = "quux"]/@fooish',
                new XName(Document::XSD_NS, 'boolean'),
                'boolean',
                false
            ],
            [
                '*//*[local-name() = "quux"]/@fooable',
                new XName(self::BAR_NS, 'Fooable'),
                '\stdClass',
                (object)[ 'PARTIALLY' ]
            ]
        ];
    }
}
