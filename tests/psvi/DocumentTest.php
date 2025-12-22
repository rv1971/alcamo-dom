<?php

namespace alcamo\dom\psvi;

use alcamo\dom\DocumentCache;
use alcamo\exception\DataValidationFailed;
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public const BAR_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'schema' . DIRECTORY_SEPARATOR
        . 'bar.xml';

    public const BAR2_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'bar.xml';

    public static function setUpBeforeClass(): void
    {
        DocumentCache::getInstance()->init();
    }

    public function testValidateIdrefs(): void
    {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())
                ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml')
        );

        $doc->validateIdRefs();

        $this->assertInstanceOf(Document::class, $doc);
    }

    public function testValidateIdrefsExceptionRef(): void
    {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())
                ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo-invalid-ref.xml')
        );

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage('no ID found for IDREF "BAR4"');

        $doc->validateIdRefs();
    }

    public function testValidateIdrefsExceptionRefs(): void
    {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())
                ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo-invalid-refs.xml')
        );

        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage('no ID found for IDREFS item "BAR5"');

        $doc->validateIdRefs();
    }

    public function testClearCache(): void
    {
        $doc = (new DocumentFactory())->createFromUri(
            (new FileUriFactory())->create(self::BAR_PATH)
        );

        $schema = $doc->getSchema();
        $converter = $doc->getConverter();
        $elementDecoratorMap = $doc->getElementDecoratorMap();

        $doc->loadUri((new FileUriFactory())->create(self::BAR2_PATH));

        $this->assertNotSame($schema, $doc->getSchema());
        $this->assertNotSame($converter, $doc->getConverter());
        $this->assertNotSame(
            $elementDecoratorMap,
            $doc->getElementDecoratorMap()
        );
    }
}
