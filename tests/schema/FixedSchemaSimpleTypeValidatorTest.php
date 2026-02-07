<?php

namespace alcamo\dom\schema;

use alcamo\dom\DocumentFactory;
use alcamo\uri\FileUriFactory;
use alcamo\xml\{NamespaceConstantsInterface, XName};
use PHPUnit\Framework\TestCase;

class FixedSchemaSimpleTypeValidatorTest extends TestCase implements NamespaceConstantsInterface
{
    public const BASE_PATH = __DIR__ . DIRECTORY_SEPARATOR;

    public const QUX_NS = 'https://qux.example.edu';

    public const TEST_DATA = [
        '42' => [ [ self::XSD_NS, 'integer' ] ],
        'x42' => [
            [ self::XSD_NS, 'integer' ],
            "'x42' is not a valid value of the atomic type 'xs:integer'."
        ],
        'qualified' => [ [ self::XSD_NS, 'formChoice' ] ],
        'QUALIFIED' => [
            [ self::XSD_NS, 'formChoice' ],
            "[facet 'enumeration'] The value 'QUALIFIED' is not an element of the set {'qualified', 'unqualified'}."
        ],
        'extension restriction' => [ [ self::QUX_NS, 'ListOfEnum' ] ],
        'extension restiction' => [
            [ self::QUX_NS, 'ListOfEnum' ],
            "[facet 'enumeration'] The value 'restiction' is not an element "
            . "of the set {'substitution', 'extension', 'restriction', 'list', 'union'}.\n"
            . "'extension restiction' is not a valid value of the list type "
            . "'{https://qux.example.edu}ListOfEnum'."
        ]
    ];

    public function testValidate(): void
    {
        $fileUriFactory = new FileUriFactory();

        $validator = FixedSchemaSimpleTypeValidator::newFromSchema(
            (new SchemaFactory())->createFromUris(
                [
                    $fileUriFactory->create(self::BASE_PATH . 'qux.1.xsd')
                ]
            )
        );

        $valueTypeXNamePairs = [];
        $expectedErrors = [];

        foreach (self::TEST_DATA as $key => $data) {
            $valueTypeXNamePairs["x$key"] = [ $key, new XName(...$data[0]) ];

            if (isset($data[1])) {
                $expectedErrors[$key] = $data[1];
            }
        }

        $errors = $validator->validate($valueTypeXNamePairs);

        $this->assertSame(count($expectedErrors), count($errors));

        foreach ($expectedErrors as $key => $expectedError) {
            $this->assertSame($expectedError, $errors["x$key"]);
        }
    }
}
