<?php

namespace alcamo\dom\extended;

use PHPUnit\Framework\TestCase;
use alcamo\rdfa\Lang;
use alcamo\xml\XName;

class FooAttr extends Attr
{
    public const ELEMENT_ATTR_CONVERTERS =
        [
            'http://foo.example.org' => [
                'foo' => [
                    'qux' => self::class . '::quxConv'
                ]
            ]
        ]
        + parent::ELEMENT_ATTR_CONVERTERS;

    public static function quxConv($value)
    {
        return "qux{$value}qux";
    }
}

class FooDocument extends Document
{
    public const NSS =
        [
            'qux' => 'http://qux.example.org'
        ]
        + parent::NSS;

    public const NODE_CLASSES =
        [
            'DOMAttr' => FooAttr::class
        ]
        + parent::NODE_CLASSES;
}

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
        $doc = FooDocument::newFromUrl(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'foo.xml'
        )->conserve();

        return [
            'lang' => [
                $doc->documentElement,
                'xml:lang',
                Lang::newFromPrimary('oc')
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
            ],
            'qux' => [
                $doc->documentElement,
                'qux',
                'qux42-43qux'
            ],
            'qux:qux' => [
                $doc->documentElement,
                'qux:qux',
                '123'
            ]
        ];
    }
}
