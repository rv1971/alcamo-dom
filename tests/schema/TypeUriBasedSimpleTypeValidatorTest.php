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

        $validator1 = TypeUriBasedSimpleTypeValidator::newFromBaseUrl($baseUrl);

        $this->assertSame(
            $baseUrl,
            $validator1->getDocumentFactory()->getBaseUri()
        );

        $validator2 = new TypeUriBasedSimpleTypeValidator();

        $this->assertNull($validator2->getDocumentFactory()->getBaseUri());
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
        $validator = TypeUriBasedSimpleTypeValidator::newFromBaseUrl(
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
                    [ 'quux', 'tests/foo2.xsd#EnumUnion' ]
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
                    'x' => [ '42 43 x 44', 'tests/foo2.xsd#ListOfNamedItemType' ],
                    [ '42 43 44', 'tests/foo2.xsd#ListOfNamedItemType' ],
                    'quuux' => [ 'quuux', 'tests/foo2.xsd#EnumUnion' ]
                ],
                [
                    1 => "'truex' is not a valid value of the atomic type 'xs:boolean'.",
                    2 => "'1970-01--01' is not a valid value of the atomic type 'xs:date'.",
                    'x' => "'x' is not a valid value of the atomic type "
                    . "'xs:integer'.\n'42 43 x 44' is not a valid value of "
                    . "the list type '{http://foo2.example.org}ListOfNamedItemType'.",
                    'quuux' => "'quuux' is not a valid value of the union type '{http://foo2.example.org}EnumUnion'."
                ]
            ]
        ];
    }
}
