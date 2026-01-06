<?php

namespace alcamo\dom\psvi;

use alcamo\dom\DocumentCache;
use alcamo\dom\xsd\Decorator as XsdDecorator;
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class BazDecorator extends HavingDocumentationDecorator
{
}

class QuxDecorator extends HavingDocumentationDecorator
{
}

class QuuxDecorator extends HavingDocumentationDecorator
{
}

class MyDocument extends Document
{
    public const BAR_NS = 'https://bar.example.com';

    public const ELEMENT_DECORATOR_MAP =
        [
            self::BAR_NS . ' Baz' => BazDecorator::class,
            self::BAR_NS . ' Quux' => QuuxDecorator::class
        ]
    + parent:: ELEMENT_DECORATOR_MAP;
}

class ElementTest extends TestCase
{
    public const BAR_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'schema' . DIRECTORY_SEPARATOR
        . 'bar.xml';

    public const BAR_NS = MyDocument::BAR_NS;

    public static function setUpBeforeClass(): void
    {
        DocumentCache::getInstance()->init();
    }

    /**
     * @dataProvider propsProvider
     */
    public function testProps(
        $xPath,
        $expectedLocalName,
        $expectedTypeLineNo,
        $expectedDecoratorClass,
        $expectedValue
    ): void {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())->create(self::BAR_PATH),
            MyDocument::class
        );

        $element = $doc->query($xPath)[0];

        $this->assertSame($expectedLocalName, $element->localName);

        if (isset($expectedTypeLineNo)) {
            $this->assertSame(
                $expectedTypeLineNo,
                $element->getType()->getXsdElement()->getLineNo()
            );
        } else {
            $this->assertEquals(
                new XName(Document::XSD_NS, 'anyType'),
                $element->getType()->getXName()
            );
        }

        $this->assertInstanceOf(
            $expectedDecoratorClass,
            $element->getDecorator()
        );

        $this->assertSame($expectedValue, $element->getValue());
    }

    public function propsProvider(): array
    {
        return [
            [
                '*',
                'bar',
                14,
                HavingDocumentationDecorator::class,
                'Lorem ipsum.4243'
            ],
            [
                '*/xsd:annotation',
                'annotation',
                1286,
                XsdDecorator::class,
                ''
            ],
            [
                '*/xsd:annotation/xsd:appinfo',
                'appinfo',
                1259,
                XsdDecorator::class,
                ''
            ],
            [
                '*/xsd:annotation/xsd:appinfo/*',
                'corge',
                null,
                HavingDocumentationDecorator::class,
                ''
            ],
            [
                '*/*[local-name() = "baz"]',
                'baz',
                31,
                BazDecorator::class,
                ''
            ],
            [
                '*/*[@xsi:type]',
                'baz',
                51,
                BazDecorator::class,
                'Lorem ipsum.4243'
            ],
            [
                '*/*[@xsi:type]/*[@xsi:type]',
                'baz',
                51,
                BazDecorator::class,
                '4243'
            ],
            [
                '*//*[local-name() = "qux"]',
                'qux',
                63,
                HavingDocumentationDecorator::class,
                42
            ],
            [
                '*//*[local-name() = "quux"]',
                'quux',
                39,
                QuuxDecorator::class,
                ''
            ]
        ];
    }
}
