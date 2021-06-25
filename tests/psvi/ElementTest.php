<?php

namespace alcamo\dom\psvi;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\Schema;
use alcamo\dom\schema\component\{AtomicType, ComplexType};
use alcamo\xml\XName;

class ElementTest extends TestCase
{
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';

    /**
     * @dataProvider getTypeProvider
     */
    public function testGetType(
        $element,
        $expectedTypeClass,
        $expectedTypeXName,
        $expectedFile,
        $expectedNodePath
    ) {
        $type = $element->getType();

        if (isset($expectedTypeClass)) {
            $this->assertInstanceOf($expectedTypeClass, $type);
        } else {
            $this->assertNull($type);
        }

        if (isset($expectedTypeXName)) {
            $this->assertEquals($expectedTypeXName, $type->getXName());
        } elseif (isset($type)) {
            $this->assertNull($type->getXName());
        }

        if (isset($expectedFile)) {
            $this->assertSame(
                $expectedFile,
                basename($type->getXsdElement()->ownerDocument->documentURI)
            );

            $this->assertSame(
                $expectedNodePath,
                $type->getXsdElement()->getNodePath()
            );
        } else {
            $this->assertNull($type);
        }
    }

    public function getTypeProvider()
    {
        $doc = Document::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        )->conserve();

        $quxDoc = Document::newFromUrl(
            'file://' . dirname(__DIR__) . '/qux.xml'
        )->conserve();

        return [
            [
                $doc->documentElement,
                ComplexType::class,
                null,
                'foo.xsd',
                '/xsd:schema/xsd:element[1]/xsd:complexType'
            ],
            [
                $doc->documentElement->firstChild,
                ComplexType::class,
                null,
                'rdfs.xsd',
                '/xsd:schema/xsd:element[1]/xsd:complexType'
            ],
            [
                $doc["x"],
                ComplexType::class,
                new XName(self::FOO_NS, 'Bar'),
                'foo.xsd',
                '/xsd:schema/xsd:complexType[3]'
            ],
            [
                $doc["b"],
                ComplexType::class,
                null,
                'foo.xsd',
                '/xsd:schema/xsd:complexType[3]'
                . '/xsd:complexContent/xsd:extension/xsd:sequence/'
                . 'xsd:element/xsd:complexType'
            ],
            [
                $doc["qux"],
                ComplexType::class,
                new XName(self::XSD_NS, 'anyType'),
                'XMLSchema.xsd',
                '/xs:schema/xs:complexType[29]'
            ],
            [
                $doc["qux"]->firstChild,
                AtomicType::class,
                new XName(self::XSD_NS, 'short'),
                'XMLSchema.xsd',
                '/xs:schema/xs:simpleType[46]'
            ],
            [
                $quxDoc->documentElement,
                ComplexType::class,
                new XName(self::XSD_NS, 'anyType'),
                'XMLSchema.xsd',
                '/xs:schema/xs:complexType[29]'
            ]
        ];
    }
}
