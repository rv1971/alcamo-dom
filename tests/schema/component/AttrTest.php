<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class AttrTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider getRefAttrProvider
     */
    public function testGetRefAttr($attr, $expectedRefAttr)
    {
        $this->assertSame($attr->getRefAttr(), $expectedRefAttr);
    }

    public function getRefAttrProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            'noref' => [
                new Attr(
                    $fooSchema,
                    $fooSchema
                        ->getGlobalType(new XName(self::XSD_NS, 'annotated'))
                        ->getXsdElement()->query('.//xsd:attribute')[0]
                ),
                null
            ],
            'ref' => [
                new Attr(
                    $fooSchema,
                    $fooSchema
                        ->getGlobalElement(new XName(self::XSD_NS, 'schema'))
                        ->getXsdElement()->query('.//xsd:attribute[@ref]')[0]
                ),
                $fooSchema
                    ->getGlobalAttr(new XName(self::XML_NS, 'lang'))
            ]
        ];
    }

    /**
     * @dataProvider getNamedTypeProvider
     */
    public function testGetNamedType($schema, $attrElement, $expectedTypeXName)
    {
        $attr = new Attr($schema, $attrElement);

        $expectedType = $schema->getGlobalType($expectedTypeXName);

        $this->assertInstanceOf(TypeInterface::class, $attr->getType());
        $this->assertSame($attr->getType(), $expectedType);
    }

    public function getNamedTypeProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $fooTypeElem = $fooSchema
            ->getGlobalElement(new XName(self::FOO_NS, 'foo'))
            ->getXsdElement();

        return [
            'ref' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@ref="quux"]')[0],
                new XName(self::XSD_NS, 'short')
            ],
            'type' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@name="bar"]')[0],
                new XName(self::XSD_NS, 'boolean')
            ],
            'anySimpleType' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@name="foobar"]')[0],
                new XName(self::XSD_NS, 'anySimpleType')
            ]
        ];
    }

    /**
     * @dataProvider getAnonymousTypeProvider
     */
    public function testGetAnonymousType(
        $schema,
        $attrElement,
        $expectedTypeClass,
        $expectedTypeParam
    ) {
        $attr = new Attr($schema, $attrElement);

        $this->assertInstanceOf($expectedTypeClass, $attr->getType());

        switch ($expectedTypeClass) {
            case AtomicType::class:
                $this->assertEquals(
                    $expectedTypeParam,
                    $attr->getType()->getBaseType()->getXName()
                );
                break;

            case ListType::class:
                $this->assertEquals(
                    $expectedTypeParam,
                    $attr->getType()->getItemType()->getXName()
                );
                break;
        }
    }

    public function getAnonymousTypeProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        $fooTypeElem = $fooSchema
            ->getGlobalElement(new XName(self::FOO_NS, 'foo'))
            ->getXsdElement();

        return [
            'atomic' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@name="foofoofoo"]')[0],
                AtomicType::class,
                new XName(self::XSD_NS, 'string')
            ],
            'list' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@name="barbarbar"]')[0],
                ListType::class,
                new XName(self::XSD_NS, 'token')
            ],
            'list2' => [
                $fooSchema,
                $fooTypeElem->query('.//xsd:attribute[@name="bazbazbaz"]')[0],
                ListType::class,
                new XName(self::XSD_NS, 'integer')
            ]
        ];
    }
}
