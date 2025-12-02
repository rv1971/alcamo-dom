<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\Schema;
use alcamo\uri\FileUriFactory;
use alcamo\xml\XName;
use PHPUnit\Framework\TestCase;

class NotationTest extends TestCase
{
    public const FOO_NS = 'http://foo.example.org';

    public const BAR_NS = 'https://bar.example.com#';

    /* This also tests class AbstractXsdComponent. */
    public function testProps(): void
    {
        $fooUri = (new FileUriFactory())
            ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xsd');

        /* Contains XMLSchema.xsd as built-in. */
        $schema = Schema::newFromUris([ $fooUri ]);

        $notation = $schema->getGlobalNotation(self::FOO_NS . ' FooNotation');

        $this->assertSame(
            'application/x-foo',
            $notation->getXsdElement()->public
        );

        $this->assertEquals(
            new XName(self::FOO_NS, 'FooNotation'),
            $notation->getXName()
        );

        $this->assertSame(
            $fooUri . '#FooNotation',
            (string)$notation->getUri()
        );

        $this->assertSame(
            false,
            $notation->getAppinfoMeta(self::BAR_NS . 'fooish')->content
        );

        $this->assertSame(
            true,
            $notation->getAppinfoMeta(self::BAR_NS . 'fooable')->content
        );

        $this->assertNull($notation->getAppinfoMeta(self::BAR_NS . 'barish'));

        $this->assertSame(
            'http://foo.example.org/documentation/FooNotation',
            $notation->getAppinfoLink(Schema::DC_NS . 'seeAlso')->href
        );

        $this->assertSame(
            'http://foo.example.org/FooNotation/coolVersion',
            $notation->getAppinfoLink(Schema::DC_NS . 'hasVersion')->href
        );
    }
}
