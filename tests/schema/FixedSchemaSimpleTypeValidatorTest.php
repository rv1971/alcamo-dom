<?php

namespace alcamo\dom\schema;

use PHPUnit\Framework\TestCase;
use alcamo\ietf\Uri;
use alcamo\xml\XName;

class FixedSchemaSimpleTypeValidatorTest extends TestCase
{
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    public function testGetXsdText()
    {
        $validator = new FixedSchemaSimpleTypeValidator(
            [
                [ 'http://foo.example.org', 'foo.xsd' ],
                [ 'http://bar.example.org', 'bar.xsd' ]
            ]
        );

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<schema xmlns="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">'
            . "<import namespace='http://foo.example.org' schemaLocation='foo.xsd'/>"
            . "<import namespace='http://bar.example.org' schemaLocation='bar.xsd'/>"
            . '<element name="x">'
            . '<complexType>'
            . '<sequence>'
            . '<element name="y" maxOccurs="unbounded"/>'
            . '</sequence>'
            . '</complexType>'
            . '</element>'
            . '</schema>',
            $validator->getXsdText()
        );
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(
        $validator,
        $valueTypeXNamePairs,
        $expectedResult
    ) {
        $result = $validator->validate($valueTypeXNamePairs);

        $this->assertSame(count($expectedResult), count($result));

        foreach ($result as $index => $msg) {
            $this->assertSame(
                $expectedResult[$index],
                substr($msg, 0, strlen($expectedResult[$index]))
            );
        }
    }

    public function validateProvider()
    {
        $validator = FixedSchemaSimpleTypeValidator::newFromSchema(
            Schema::newFromUrls(
                [
                    Uri::newFromFilesystemPath(
                        dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                        . 'xsd' . DIRECTORY_SEPARATOR . 'XMLSchema.xsd'
                    )
                ]
            )
        );

        return [
            'no-errors' => [
                $validator,
                [
                    [ 'qualified', new XName(self::XSD_NS, 'formChoice') ],
                    [ '#all', new XName(self::XSD_NS, 'derivationSet') ],
                    [
                        'list union',
                        new XName(self::XSD_NS, 'fullDerivationSet')
                    ],
                    [ 42, new XName(self::XSD_NS, 'allNNI') ],
                    [
                        '##targetNamespace ##local http://www.example.org',
                        new XName(self::XSD_NS, 'namespaceList')
                    ],
                    [ 'true', new XName(self::XSD_NS, 'boolean') ],
                    [ 43.44, new XName(self::XSD_NS, 'float') ],
                    [ 'P100Y', new XName(self::XSD_NS, 'duration') ]
                ],
                []
            ],
            'errors' => [
                $validator,
                [
                    [ 'foo', new XName(self::XSD_NS, 'formChoice') ],
                    [ '#all', new XName(self::XSD_NS, 'derivationSet') ],
                    [
                        'list union bar',
                        new XName(self::XSD_NS, 'fullDerivationSet')
                    ],
                    [ -42, new XName(self::XSD_NS, 'allNNI') ],
                    [
                        '##targetNamespace ##local http://www.example.org',
                        new XName(self::XSD_NS, 'namespaceList')
                    ],
                    [ 'true', new XName(self::XSD_NS, 'boolean') ],
                    [ 43.44, new XName(self::XSD_NS, 'float') ],
                    [ 'P100X', new XName(self::XSD_NS, 'duration') ]
                ],
                [
                    0 =>
                    "[facet 'enumeration'] The value 'foo' is not an element of the set {'qualified', 'unqualified'}.",
                    2 => "'list union bar' is not a valid value of the union "
                    . "type '{http://www.w3.org/2001/XMLSchema}fullDerivationSet'.",
                    3 => "'-42' is not a valid value of the union type '{http://www.w3.org/2001/XMLSchema}allNNI'.",
                    7 => "'P100X' is not a valid value of the atomic type 'xs:duration'."
                ]
            ]
        ];
    }
}
