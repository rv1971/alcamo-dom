<?php

namespace alcamo\dom\schema;

use PHPUnit\Framework\TestCase;
use alcamo\uri\{Uri, FileUriFactory};
use alcamo\xml\XName;

class TypeUriBasedSimpleTypeValidatorTest extends TestCase
{
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    public function testGetBaseUrl()
    {
        $baseUrl = new Uri('http://foo.example.org');

        $validator1 = new TypeUriBasedSimpleTypeValidator($baseUrl);

        $this->assertSame($baseUrl, $validator1->getbaseUrl());

        $validator2 = new TypeUriBasedSimpleTypeValidator();

        $this->assertEquals(new Uri(), $validator2->getbaseUrl());
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(
        $validator,
        $valueTypeUriPairs,
        $expectedResult
    ) {
        $this->assertSame(
            $expectedResult,
            $validator->validate($valueTypeUriPairs)
        );
    }

    public function validateProvider()
    {
        $validator = new TypeUriBasedSimpleTypeValidator(
            (new FileUriFactory())->create(
                dirname(__DIR__) . DIRECTORY_SEPARATOR
            )
        );

        return [
            'no-errors' => [
                $validator,
                [
                    [ 'foo', 'xsd/XMLSchema.xsd#string' ],
                    [ 'true', 'xsd/XMLSchema.xsd#boolean' ],
                    [ '1970-01-01', 'xsd/XMLSchema.xsd#date' ],
                    [ 'ach-UG', 'xsd/XMLSchema.xsd#language' ],
                    [ 'alice', 'tests/foo2a.xsd#UnionOfNamed' ],
                    [ '42 43 44', 'tests/foo2.xsd#ListOfNamedItemType' ],
                    [ 'bob', 'tests/foo2a.xsd#UnionOfNamed' ],
                    [ 'claire', 'tests/foo2a.xsd#UnionOfNamed' ],
                    [ 'quux', 'tests/foo2.xsd#FooBarType' ]
                ],
                []
            ],
            'errors' => [
                $validator,
                [
                    [ 'foo', 'xsd/XMLSchema.xsd#string' ],
                    [ 'truex', 'xsd/XMLSchema.xsd#boolean' ],
                    [ '1970-01--01', 'xsd/XMLSchema.xsd#date' ],
                    [ 'ach-UG', 'xsd/XMLSchema.xsd#language' ],
                    [ '42 43 x 44', 'tests/foo2.xsd#ListOfNamedItemType' ],
                    [ '42 43 44', 'tests/foo2.xsd#ListOfNamedItemType' ],
                    [ 'quuux', 'tests/foo2.xsd#FooBarType' ]
                ],
                [
                    0 => "'truex' is not a valid value of the atomic type 'xs:boolean'.",
                    1 => "'1970-01--01' is not a valid value of the atomic type 'xs:date'.",
                    2 => "'x' is not a valid value of the atomic type "
                    . "'xs:integer'.\n'42 43 x 44' is not a valid value of "
                    . "the list type '{http://foo2.example.org}ListOfNamedItemType'.",
                    3 => "'quuux' is not a valid value of the union type '{http://foo2.example.org}EnumUnion'."
                ]
            ]
        ];
    }
}
