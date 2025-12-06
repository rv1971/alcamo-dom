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

        $builtinSchema = Schema::getBuiltinSchema();

        $this->assertSame(1, count($cache));

        $this->assertSame('', $builtinSchema->getCacheKey());

        $this->assertSame($builtinSchema, $cache['']);

        $fileUriFactory = new FileUriFactory();

        $fooUri = $fileUriFactory->create(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . 'foo.xsd'
        );

        $barUri = $fileUriFactory->create(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . 'bar.xsd'
        );

        $fooBarSchema = Schema::newFromUris([ $fooUri, $barUri ]);

        $this->assertSame(2, count($cache));

        $this->assertSame($fooBarSchema, $cache[$fooBarSchema->getCacheKey()]);

        $barFooSchema = Schema::newFromUris([ $barUri, $fooUri ]);

        $this->assertSame(2, count($cache));

        $this->assertSame($fooBarSchema, $barFooSchema);
    }
}
