<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class ElementTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider getRefElementProvider
     */
    public function testGetRefElement(
        $schema,
        $elementElement,
        $expectedRefElement
    ) {
        $element = new Element($schema, $elementElement);

        $this->assertSame($element->getRefElement(), $expectedRefElement);
    }

    public function getRefElementProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            'noref' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalGroup(new XName(self::XSD_NS, 'typeDefParticle'))
                    ->getXsdElement()->query('.//xsd:element')[0],
                null
            ],
            'ref' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalType(new XName(self::XSD_NS, 'annotated'))
                    ->getXsdElement()->query('.//xsd:element')[0],
                $fooSchema
                    ->getGlobalElement(new XName(self::XSD_NS, 'annotation'))
            ]
        ];
    }

    /**
     * @dataProvider getNamedTypeProvider
     */
    public function testGetNamedType(
        $schema,
        $elementElement,
        $expectedTypeXName
    ) {
        $element = new Element($schema, $elementElement);

        $expectedType = $schema->getGlobalType($expectedTypeXName);

        $this->assertInstanceOf(TypeInterface::class, $element->getType());
        $this->assertSame($element->getType(), $expectedType);
    }

    public function getNamedTypeProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            'ref' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalType(new XName(self::FOO_NS, 'QuuxContainer'))
                    ->getXsdElement()->query('.//xsd:element')[0],
                new XName(self::XSD_NS, 'duration')
            ],
            'type' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalGroup(new XName(self::XSD_NS, 'typeDefParticle'))
                    ->getXsdElement()->query('.//xsd:element')[0],
                new XName(self::XSD_NS, 'groupRef')
            ],
            'anyType' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalElement(new XName(self::FOO_NS, 'anyQuux'))
                    ->getXsdElement(),
                new XName(self::XSD_NS, 'anyType')
            ]
        ];
    }

    /**
     * @dataProvider getAnonymousTypeProvider
     */
    public function testGetAnonymousType(
        $schema,
        $elementElement,
        $expectedTypeClass,
        $expectedTypeParam
    ) {
        $element = new Element($schema, $elementElement);

        $this->assertInstanceOf($expectedTypeClass, $element->getType());

        switch ($expectedTypeClass) {
            case AtomicType::class:
                $this->assertEquals(
                    $expectedTypeParam,
                    $element->getType()->getBaseType()->getXName()
                );
                break;

            case ListType::class:
                $this->assertEquals(
                    $expectedTypeParam,
                    $element->getType()->getItemType()->getXName()
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
                $fooSchema
                    ->getGlobalElement(new XName(self::FOO_NS, 'corge1'))
                    ->getXsdElement(),
                AtomicType::class,
                new XName(self::XSD_NS, 'language')
            ],
            'complexType' => [
                $fooSchema,
                $fooSchema
                    ->getGlobalElement(new XName(self::XSD_NS, 'schema'))
                    ->getXsdElement(),
                ComplexType::class,
                null
            ]
        ];
    }
}
