<?php

namespace alcamo\dom\schema\component;

use PHPUnit\Framework\TestCase;
use alcamo\dom\extended\Document;
use alcamo\dom\schema\Schema;
use alcamo\xml\XName;

class AttrGroupTest extends TestCase
{
    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';
    public const FOO_NS = 'http://foo.example.org';
    public const FOO2_NS = 'http://foo2.example.org';

    /**
     * @dataProvider getAttrsProvider
     */
    public function testGetAttrs($attrGroup, $expectedAttrs)
    {
        $this->assertSame(
            count($expectedAttrs),
            count($attrGroup->getAttrs())
        );

        $i = 0;
        foreach ($attrGroup->getAttrs() as $name => $attr) {
            $this->assertInstanceOf(Attr::class, $attr);
            $this->assertSame($name, (string)$attr->getXName());

            $this->assertSame($expectedAttrs[$i++], $name);
        }
    }

    public function getAttrsProvider()
    {
        $fooSchema = Schema::newFromDocument(
            Document::newFromUrl(
                'file:///' . dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'foo.xml'
            )
        );

        return [
            'xsd:occurs' => [
                $fooSchema->getGlobalAttrGroup(
                    new XName(self::XSD_NS, 'occurs')
                ),
                [
                    'minOccurs', 'maxOccurs'
                ]
            ],
            'xsd:defRef' => [
                $fooSchema->getGlobalAttrGroup(
                    new XName(self::XSD_NS, 'defRef')
                ),
                [
                    'name', 'ref'
                ]
            ],
            'foo2:NestedAttrGroup' => [
                $fooSchema->getGlobalAttrGroup(
                    new XName(self::FOO2_NS, 'NestedAttrGroup')
                ),
                [
                    'minOccurs', 'maxOccurs', self::XML_NS . ' id', 'foo'
                ]
            ]
        ];
    }
}
