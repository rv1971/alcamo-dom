<?php

namespace alcamo\dom\decorated;

use alcamo\dom\xsd\{Decorator as XsdDecorator, Enumerator};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class MyDecoratorAsterisk extends HavingDocumentationDecorator
{
    public function __toString(): string
    {
        return "*" . $this->handler_->textContent . "*";
    }
}

class MyDecoratorPlus extends HavingDocumentationDecorator
{
    public function __toString(): string
    {
        return "+" . $this->handler_->textContent . "+";
    }
}

class MyElement extends Element
{
    public const DECORATOR_MAP =
        [
            'http://foo.example.org' => [
                'text' => MyDecoratorAsterisk::class,
                '*' => MyDecoratorPlus::class
            ]
        ]
    + parent::DECORATOR_MAP;
}

class MyDocument extends Document
{
    public const NODE_CLASSES =
        [
            'DOMElement' => MyElement::class
        ]
        + parent::NODE_CLASSES;
}

class ElementTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    private static $factory_;
    private static $doc_;

    public static function setUpBeforeClass(): void
    {
        self::$factory_ =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        self::$doc_ =
            self::$factory_->createFromUri('foo.xml', MyDocument::class, false);
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString($xPath, $expectedClass, $expectedString): void
    {
        $element = self::$doc_->query($xPath)[0];

        $this->assertSame($expectedClass, get_class($element->getDecorator()));

        $this->assertSame($expectedString, (string)$element);
    }

    public function toStringProvider(): array
    {
        return [
            [ '*/*[2]/*[1]', MyDecoratorAsterisk::class, '*Lorem ipsum.*' ],
            [ '*/*[2]/*[2]', MyDecoratorPlus::class, '+L. i.+' ],
            [
                '*/xsd:simpleType',
                XsdDecorator::class,
                'Not a valid type element'
            ],

            /* This also tests class alcamo::dom::xsd::enumerator */
            [
                '*/xsd:enumeration',
                Enumerator::class,
                'Not a valid enumeration element'
            ],
        ];
    }
}
