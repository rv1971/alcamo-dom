<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\ietf\Lang;
use alcamo\xml\XName;

class AttrTest extends TestCase
{
    /**
     * @dataProvider getValueProvider
     */
    public function testgetValue($attr, $attrName, $expectedResult)
    {
        if (is_object($expectedResult)) {
            $this->assertInstanceOf(
                get_class($expectedResult),
                $attr->$attrName
            );
        }

        $this->assertEquals($expectedResult, $attr->$attrName);
    }

    public function getValueProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            'lang' => [
                $doc->documentElement,
                'xml:lang',
                new Lang('oc')
            ],
            'schemaLocation' => [
                $doc->documentElement,
                'xsi:schemaLocation',
                [
                    'http://foo.example.org' => 'foo.xsd',
                    'http://www.w3.org/2000/01/rdf-schema#'
                    => '../xsd/rdfs.xsd'
                ]
            ],
            'type' => [
                $doc->documentElement->firstChild->nextSibling,
                'xsi:type',
                new XName('http://foo.example.org', 'Bar')
            ]
        ];
    }
}
