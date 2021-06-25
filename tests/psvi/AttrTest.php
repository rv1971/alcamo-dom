<?php

namespace alcamo\dom\psvi;

use PHPUnit\Framework\TestCase;
use alcamo\dom\schema\Schema;
use alcamo\dom\schema\component\{AtomicType, PredefinedSimpleType};
use alcamo\ietf\{Lang, Uri};
use alcamo\xml\XName;

class AttrTest extends TestCase
{
    public const DC_NS  = '"http://purl.org/dc/terms/';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';

    /**
     * @dataProvider getTypeProvider
     */
    public function testGetType(
        $attr,
        $expectedTypeClass,
        $expectedTypeXName,
        $expectedFile,
        $expectedNodePath
    ) {
        $type = $attr->getType();

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
        }
    }

    public function getTypeProvider()
    {
        $doc = Document::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        )->conserve();

        return [
            [
                $doc->documentElement->getAttributeNode('dc:source'),
                AtomicType::class,
                new XName(self::XSD_NS, 'anyURI'),
                'XMLSchema.xsd',
                '/xs:schema/xs:simpleType[26]'
            ],
            [
                $doc->documentElement->getAttributeNode('bar'),
                AtomicType::class,
                new XName(self::XSD_NS, 'boolean'),
                'XMLSchema.xsd',
                '/xs:schema/xs:simpleType[11]'
            ],
            // element type is anyType
            [
                $doc['qux']->getAttributeNode('qux'),
                PredefinedSimpleType::class,
                new XName(self::XSD_NS, 'anySimpleType'),
                null,
                null
            ],
            // attribute is unknwon
            [
                $doc->documentElement->getAttributeNode('qux:qux'),
                PredefinedSimpleType::class,
                new XName(self::XSD_NS, 'anySimpleType'),
                null,
                null
            ],
            // attribute type is anySimpleType
            [
                $doc->documentElement->getAttributeNode('qux'),
                PredefinedSimpleType::class,
                new XName(self::XSD_NS, 'anySimpleType'),
                null,
                null
            ],
        ];
    }

    /**
     * @dataProvider getValueProvider
     */
    public function testGetValue($value, $expectedValue)
    {
        switch (true) {
            case is_object($expectedValue):
            case is_array($expectedValue):
                $this->assertEquals($expectedValue, $value);
                break;

            default:
                $this->assertSame($expectedValue, $value);
        }
    }

    public function getValueProvider()
    {
        $doc = Document::newFromUrl(
            'file://' . dirname(__DIR__) . '/foo.xml'
        )->conserve();

        $enums = $doc->getSchema()
            ->getGlobalType(new XName(self::XSD_NS, 'typeDerivationControl'))
            ->getEnumerators();

        return [
            'xml:lang' => [
                $doc->documentElement->{'xml:lang'},
                new Lang('oc')
            ],
            'dc:source' => [
                $doc->documentElement->{'dc:source'},
                new Uri('http://www.example.org/foo')
            ],
            'qux' => [
                $doc->documentElement->qux,
                '42-43'
            ],
            'bar' => [
                $doc->documentElement->bar,
                true
            ],
            'baz' => [
                $doc->documentElement->baz,
                false
            ],
            'barbaz' => [
                $doc->documentElement->barbaz,
                42
            ],
            'datetime' => [
                $doc['corge']->datetime,
                new \DateTime('2021-02-17')
            ],
            'safecurie' => [
                $doc['corge']->safecurie,
                new Uri('http://foo.example.org/bar?baz=qux')
            ],
            'list' => [
                $doc['corge']->list,
                [ 'foo', 'foo', 'foo', 'bar' ]
            ],
            'shorts' => [
                $doc['corge']->shorts,
                [ 1 => 1, 2 => 2, 3 => 3, -4 => -4 ]
            ],
            'enums' => [
                $doc['corge']->enums,
                [
                    'list' => $enums['list'],
                    'union' => $enums['union']
                ]
            ]
        ];
    }
}
