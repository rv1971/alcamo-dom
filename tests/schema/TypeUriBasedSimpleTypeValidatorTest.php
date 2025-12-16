<?php

namespace alcamo\dom\schema;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class TypeUriBasedSimpleTypeValidatorTest extends TestCase
{
    public const TEST_DATA = [
        '42' => [ '../../xsd/XMLSchema.xsd#integer' ],
        'x42' => [
            '../../xsd/XMLSchema.xsd#integer',
            "'x42' is not a valid value of the atomic type 'xs:integer'."
        ],
        'qualified' => [ '../../xsd/XMLSchema.xsd#formChoice' ],
        'QUALIFIED' => [
            '../../xsd/XMLSchema.xsd#formChoice',
            "[facet 'enumeration'] The value 'QUALIFIED' is not an element of the set {'qualified', 'unqualified'}."
        ],
        'extension restriction' => [ 'qux.1.xsd#ListOfEnum' ],
        'extension restiction' => [
            'qux.1.xsd#ListOfEnum',
            "[facet 'enumeration'] The value 'restiction' is not an element "
            . "of the set {'substitution', 'extension', 'restriction', 'list', 'union'}.\n"
            . "'extension restiction' is not a valid value of the list type "
            . "'{https://qux.example.edu}ListOfEnum'."
        ],
        '[foo:bar]' => [ 'qux.2.xsd#SafeCurie' ],
        '[foo:bar' => [
            'qux.2.xsd#SafeCurie',
            "[facet 'pattern'] The value '[foo:bar' is not accepted by the "
            . "pattern '\[(([\i-[:]][\c-[:]]*)?:)?(/[^\s/][^\s]*|[^\s/][^\s]*|[^\s]?)\]'."
        ]
    ];

    public function testValidate(): void
    {
        $validator = TypeUriBasedSimpleTypeValidator::newFromBaseUri(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        $valueTypeUriPairs = [];
        $expectedErrors = [];

        foreach (self::TEST_DATA as $key => $data) {
            $valueTypeUriPairs["x$key"] = [ $key, $data[0] ];

            if (isset($data[1])) {
                $expectedErrors[$key] = $data[1];
            }
        }

        $errors = $validator->validate($valueTypeUriPairs);

        $this->assertSame(count($expectedErrors), count($errors));

        foreach ($expectedErrors as $key => $expectedError) {
            $this->assertSame($expectedError, $errors["x$key"]);
        }
    }
}
