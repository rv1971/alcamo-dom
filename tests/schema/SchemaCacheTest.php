<?php

namespace alcamo\dom\schema;

use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class SchemaCacheTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public function testCache(): void
    {
        $cache = SchemaCache::getInstance();

        $cache->init();

        $this->assertSame(0, count($cache));

        $schemaFactory = new SchemaFactory();

        $builtinSchema = $schemaFactory->getBuiltinSchema();

        /* one entry for key "", one for the URI of XMLSchema.xsd */
        $this->assertSame(2, count($cache));

        $this->assertStringContainsString(
            'xsd/xhtml-datatypes-1.xsd',
            $cache->getKeys()[1]
        );

        $fileUriFactory = new FileUriFactory();

        $fooUri = $fileUriFactory->create(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . 'foo.xsd'
        );

        $barUri = $fileUriFactory->create(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . 'bar.xsd'
        );

        $fooBarSchema = $schemaFactory->createFromUris([ $fooUri, $barUri ]);

        $this->assertSame(4, count($cache));

        $barFooSchema = $schemaFactory->createFromUris([ $barUri, $fooUri ]);

        $this->assertSame(4, count($cache));

        $this->assertSame($fooBarSchema, $barFooSchema);
    }
}
