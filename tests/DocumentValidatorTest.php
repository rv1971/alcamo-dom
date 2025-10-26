<?php

namespace alcamo\dom;

use PHPUnit\Framework\TestCase;
use alcamo\exception\DataValidationFailed;

class ValidatedDocument extends Document
{
    public const LOAD_FLAGS = self::VALIDATE_AFTER_LOAD;
}

class DocumentValidatorTest extends TestCase
{
    public function testNoSchemaLocation()
    {
        $validator = new DocumentValidator();

        $qux = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'qux.xml'
        );

        $this->assertNull($validator->createSchemaLocationsMap($qux));

        $this->assertSame($qux, $validator->validate($qux));
    }

    public function testNoNsValidate()
    {
        $validator = new DocumentValidator();

        $bar = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'
        );

        $this->expectException(DataValidationFailed::class);

        $validator->validateAgainstXsdUrl(
            $bar,
            __DIR__ . DIRECTORY_SEPARATOR . 'baz.xsd'
        );
    }

    public function testValidate()
    {
        $validator = new DocumentValidator();

        $bar = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->assertEquals(
            [
                'http://foo.example.org',
                'http://www.w3.org/2000/01/rdf-schema#'
            ],
            array_keys($validator->createSchemaLocationsMap($bar))
        );

        $this->assertSame($bar, $validator->validate($bar));
    }

    public function testNoNsValidateException()
    {
        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'
        );

        $this->expectException(DataValidationFailed::class);

        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar-invalid.xml'
        );
    }

    public function testValidateException()
    {
        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->expectException(DataValidationFailed::class);

        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-invalid.xml'
        );
    }
}
