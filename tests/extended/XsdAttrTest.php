<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\rdfa\Lang;
use alcamo\uri\Uri;
use alcamo\xml\XName;

class XsdAttrTest extends TestCase
{
    /**
     * @dataProvider getValueProvider
     */
    public function testGetValue($elem, $attrName, $expectedValue)
    {
        switch (
            explode(
                '::',
                Attr::ELEMENT_ATTR_CONVERTERS[Document::XSD_NS]['*'][$attrName]
            )[1]
        ) {
            case 'toUri':
            case 'toXName':
            case 'toXNames':
                $this->assertEquals($expectedValue, $elem->$attrName);
                break;

            default:
                $this->assertSame($expectedValue, $elem->$attrName);
        }
    }

    public function getValueProvider()
    {
        $doc = Document::newFromUrl(
            dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
        )->conserve();

        return [
            'maxOccurs' => [
                $doc->query('//*[@maxOccurs = 1]')[0],
                'maxOccurs',
                1
            ],
            'maxOccurs-unbounded' => [
                $doc->query('//*[@maxOccurs = "unbounded"]')[0],
                'maxOccurs',
                -1
            ],
            'abstract' => [
                $doc->query('//*[@abstract = "true"]')[0],
                'abstract',
                true
            ],
            'mixed' => [
                $doc->query('//*[@mixed = "true"]')[0],
                'mixed',
                true
            ],
            'minOccurs' => [
                $doc->query('//*[@minOccurs = 0]')[0],
                'minOccurs',
                0
            ],
            'schemaLocation' => [
                $doc->query('//*[@schemaLocation = "xml.xsd"]')[0],
                'schemaLocation',
                new Uri('xml.xsd')
            ],
            'source' => [
                $doc->query('//*[@source = "http://www.w3.org/TR/xmlschema-1/#element-schema"]')[0],
                'source',
                new Uri('http://www.w3.org/TR/xmlschema-1/#element-schema')
            ],
            'system' => [
                $doc->query('//*[@system = "http://www.w3.org/2000/08/XMLSchema.xsd"]')[0],
                'system',
                new Uri('http://www.w3.org/2000/08/XMLSchema.xsd')
            ],
            'base' => [
                $doc->query('//*[@base = "xs:anyType"]')[0],
                'base',
                new Xname(Document::XSD_NS, 'anyType')
            ],
            'itemType' => [
                $doc->query('//*[@itemType = "xs:reducedDerivationControl"]')[0],
                'itemType',
                new Xname(Document::XSD_NS, 'reducedDerivationControl')
            ],
            'ref' => [
                $doc->query('//*[@ref = "xs:annotation"]')[0],
                'ref',
                new Xname(Document::XSD_NS, 'annotation')
            ],
            'type' => [
                $doc->query('//*[@type = "xs:ID"]')[0],
                'type',
                new Xname(Document::XSD_NS, 'ID')
            ],
            'memberTypes' => [
                $doc->query('//*[@memberTypes="xs:nonNegativeInteger"]')[0],
                'memberTypes',
                [ new Xname(Document::XSD_NS, 'nonNegativeInteger') ]
            ],
        ];
    }

    public function testLangCache()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        $this->assertEquals('oc', (string)$doc->documentElement->getLang());

        $doc->documentElement
            ->setAttributeNS(Document::XML_NS, 'xml:lang', 'cu');

        $this->assertEquals('cu', $doc->documentElement
            ->getAttributeNS(Document::XML_NS, 'lang'));

        // language is cached and therefore does not see the change
        $this->assertEquals('oc', (string)$doc->documentElement->getLang());
    }

    /**
     * @dataProvider attrArrayAccessProvider
     */
    public function testAttrArrayAccess(
        $elem,
        $attrName,
        $expectedIsSet,
        $expectedValue
    ) {
        $this->assertSame($expectedIsSet, isset($elem->$attrName));

        if (is_object($expectedValue)) {
            $this->assertInstanceOf(
                get_class($expectedValue),
                $elem->$attrName
            );

            $this->assertEquals($expectedValue, $elem->$attrName);
        } else {
            $this->assertSame($expectedValue, $elem->$attrName);
        }
    }

    public function attrArrayAccessProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            'without-namespace' => [
                $doc->documentElement, 'qux', true, '42-43'
            ],
            'namespace-prefix' => [
                $doc->documentElement, 'xml:lang', true, Lang::newFromPrimary('oc')
            ],
            'xname' => [
                $doc->documentElement,
                Document::XML_NS . ' lang',
                true,
                Lang::newFromPrimary('oc')
            ],
            'unset-without-namespace' => [
                $doc->documentElement, 'barbarbar', false, null
            ],
            'unset-namespace-prefix' => [
                $doc->documentElement, 'dc:title', false, null
            ],
            'unset-xname' => [
                $doc->documentElement,
                Document::RDFS_NS . ' comment',
                false,
                null
            ]
        ];
    }
}
