<?php

namespace alcamo\dom\schema;

use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class TargetNsCacheTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public function testCache(): void
    {
        $cache = TargetNsCache::getInstance();

        $cache->init();

        $initCacheCount = count($cache);

        $this->assertSame(Schema::XSD_NS, $cache[Schema::XSD_NS]);

        $this->assertSame($initCacheCount, count($cache));

        $fooXsdUri = (new FileUriFactory())->create(
            __DIR__ . DIRECTORY_SEPARATOR . 'component' . DIRECTORY_SEPARATOR
                . 'foo.xsd'
        );

        $this->assertTrue(isset($cache[$fooXsdUri]));

        $this->assertSame($initCacheCount + 1, count($cache));

        $this->assertSame(self::FOO_NS, $cache[$fooXsdUri]);

        $noNsFooXsdUri = (new FileUriFactory())->create(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
                . 'no-ns-foo.xsd'
        );

        $this->assertFalse(isset($cache[$noNsFooXsdUri]));

        $this->assertSame($initCacheCount + 2, count($cache));

        $this->assertNull($cache[$noNsFooXsdUri]);
    }
}
