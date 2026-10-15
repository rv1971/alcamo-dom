<?php

namespace alcamo\dom\schema\component;

use alcamo\dom\schema\{Schema, SchemaFactory};
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
        $schema = (new SchemaFactory())->createFromUris([ $fooUri ]);

        $fooNotation =
            $schema->getGlobalNotation(self::FOO_NS . ' FooNotation');

        $this->assertSame(
            'application/x-foo',
            $fooNotation->getXsdElement()->public
        );

        $this->assertEquals(
            new XName(self::FOO_NS, 'FooNotation'),
            $fooNotation->getXName()
        );

        $this->assertSame(
            $fooUri . '#FooNotation',
            (string)$fooNotation->getUri()
        );

        $barNotation =
            $schema->getGlobalNotation(self::FOO_NS . ' BarNotation');

        /* This tests the workaround for the `id` attribute problem explained
         * in the comment in AbstractXsdComponent::getUri(). */

        $this->assertSame(
            $fooUri . '#BarNotation',
            (string)$barNotation->getUri()
        );
    }
}
