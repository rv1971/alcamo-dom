<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\xml\XName;

class AttrTest extends TestCase
{
    /**
     * @dataProvider getValueProvider
     */
    public function testgetValue($attr, $attrName, $expectedResult)
    {
        $this->assertEquals($expectedResult, $attr->$attrName);
    }

    public function getValueProvider()
    {
        $doc = Document::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
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
