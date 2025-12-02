<?php

namespace alcamo\dom\schema;

use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class TargetNsCacheTest extends TestCase
{
    public function testInit(): void
    {
        $cache = TargetNsCache::getInstance();

        $this->assertSame(Schema::XSD_NS, $cache[Schema::XSD_NS]);

        $this->assertSame(Schema::OWL_NS, $cache[rtrim(Schema::OWL_NS, '#')]);
    }
}
