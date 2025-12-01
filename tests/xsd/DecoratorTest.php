<?php

namespace alcamo\dom\xsd;

use alcamo\dom\decorated\DocumentFactory;
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class DecoratorTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public const XSD_DIR = '..' . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'xsd' . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider getComponentXNameProvider
     */
    public function testGetComponentXName($element, $expectedXName): void
    {
        $this->assertEquals($expectedXName, $element->getComponentXName());
    }

    public function getComponentXNameProvider(): array
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $xmlSchema = $factory
            ->createFromUri(self::XSD_DIR . 'XMLSchema.xsd', null, false)
            ->conserve();

        $dc = $factory->createFromUri(self::XSD_DIR . 'dc.xsd', null, false)
            ->conserve();

        $foo = $factory->createFromUri('foo.xsd', null, false)->conserve();

        return [
            [
                $xmlSchema['schema'],
                new XName(DocumentFactory::XSD_NS, 'schema')
            ],
            [ $xmlSchema['schema']->firstChild, null ],
            [
                $xmlSchema->query(
                    '*//xsd:complexType[@name = "annotated"]//xsd:element'
                )[0],
                new XName(DocumentFactory::XSD_NS, 'annotation')
            ],
            [
                $xmlSchema->query(
                    '*//xsd:group[@name = "typeDefParticle"]//xsd:element'
                )[0],
                new XName(DocumentFactory::XSD_NS, 'group')
            ],
            [
                $xmlSchema->query(
                    '*//xsd:complexType[@name = "annotated"]//xsd:attribute'
                )[0],
                new XName(null, 'id')
            ],
            [
                $dc->query('*/xsd:simpleType[@name = "Agent"]')[0],
                new XName(DocumentFactory::DC_NS, 'Agent')
            ],
            [
                $foo->query('*/xsd:complexType/xsd:sequence/xsd:element')[0],
                new XName(null, 'baz')
            ],
            [
                $foo->query('*/xsd:complexType/xsd:sequence/xsd:element')[1],
                new XName('http://foo.example.org', 'qux')
            ],
            [
                $foo->query('*/xsd:complexType/xsd:attribute')[0],
                new XName('http://foo.example.org', 'corge')
            ]
        ];
    }

    /**
     * @dataProvider metaDataProvider
     */
    public function testMetaData(
        $element,
        $lang,
        $fallbackFlags,
        $expectedLabel,
        $expectedComment
    ): void {
        $this->assertEquals(
            $expectedLabel,
            $element->getLabel($lang, $fallbackFlags)
        );

        $this->assertEquals(
            $expectedComment,
            $element->getComment($lang, $fallbackFlags)
        );
    }

    public function metaDataProvider(): array
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $foo = $factory->createFromUri('foo.xsd', null, false)->conserve();

        return [
           [ $foo['Foo'], 'is', null, null, null ],
           [ $foo['Foo'], 'is', Decorator::FALLBACK_TO_NAME, 'Foo', null ],
           [ $foo['Foo'], 'en', null, 'Foo type', 'Example type.' ],
           [ $foo['Foo'], 'it', null, 'Tipo Foo', 'Tipo come esempio.' ],
        ];
    }
}
