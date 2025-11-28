<?php

namespace alcamo\dom;

use alcamo\exception\DataValidationFailed;
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentValidatorTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public const XSD_DIR =
        '..' . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider propsProvider
     */
    public function testProps($libXmlFlags): void
    {
        $validator = new DocumentValidator($libXmlFlags);

        $this->assertSame($libXmlFlags, $validator->getLibxmlFlags());
    }

    public function propsProvider(): array
    {
        return [
            [ 0 ],
            [ LIBXML_SCHEMA_CREATE ]
        ];
    }

    /**
     * @dataProvider createSchemaLocationsMapProvider
     */
    public function testCreateSchemaLocationsMap($docUri, $expectedMap): void
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $validator = new DocumentValidator();

        $this->assertSame(
            $expectedMap,
            $validator
                ->createSchemaLocationsMap($factory->createFromUri($docUri))
        );
    }

    public function createSchemaLocationsMapProvider(): array
    {
        return [
            [ 'foo.xml', null ],
            [ 'empty-bar.xml', [ 'https://bar.example.com' => 'bar.xsd' ] ],
            [
                'bar.xml',
                [
                    'https://bar.example.com' => 'bar.xsd',
                    'http://www.w3.org/2001/XMLSchema' => '../xsd/XMLSchema.xsd'
                ]
            ],
        ];
    }

    /**
     * @dataProvider positiveValidateProvider
     */
    public function testPositiveValidate($docUri): void
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $validator = new DocumentValidator();

        $doc = $factory->createFromUri($docUri);

        $this->assertSame($doc, $validator->validate($doc));
    }

    public function positiveValidateProvider(): array
    {
        return [
            [ 'foo.xml' ],
            [ 'no-ns-foo.xml' ],
            [ 'empty-bar.xml' ],
            [ 'empty-baz.xml' ],

            /* This triggers a warning "Skipping import of schema located at
             * 'aux-bar.xsd'" but not an error, hence validation succeeds. */
            [ 'bar.xml' ]
        ];
    }

    /**
     * @dataProvider negativeValidateProvider
     */
    public function testNegativeValidate($docUri, $expectedMsgFragment): void
    {
        $factory =
            new DocumentFactory((new FileUriFactory())->create(self::DATA_DIR));

        $validator = new DocumentValidator();

        $doc = $factory->createFromUri($docUri);

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage($expectedMsgFragment);

        $validator->validate($doc);
    }

    public function negativeValidateProvider(): array
    {
        return [
            [
                'invalid-no-ns-foo.xml',
                "invalid-no-ns-foo.xml:5 Element 'bar': "
                . "No matching global declaration available for the validation root."
            ],
            [
                'invalid-bar-1.xml',
                "Element '{https://bar.example.com}qux': This element is not expected."
            ],
            [
                'invalid-bar-2.xml',
                "Element '{https://bar.example.com}baz': This element is not expected."
            ]
        ];
    }
}
