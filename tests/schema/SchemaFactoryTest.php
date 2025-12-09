<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;
use alcamo\uri\{FileUriFactory, Uri};
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    public function testCreateFromDocument(): void
    {
        $doc = Document::newFromUri(
            (new FileUriFactory())
                ->create(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml')
        );

        $factory = new SchemaFactory();

        $schema = $factory->createFromDocument($doc);

        $xsds = [];

        foreach ($schema->getTopXsds() as $uri => $xsd) {
            $xsds[basename((new Uri($uri))->getPath())] =
                $xsd->documentElement->targetNamespace;
        }

        $this->assertSame(
            [
                'XMLSchema.xsd' => Schema::XSD_NS,
                'xml.xsd' => Schema::XML_NS,
                'bar.xsd' => 'https://bar.example.com',
                'foo.xsd' => 'http://foo.example.org'
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame($schema, $factory->createFromDocument($doc));
    }

    public function testGetBuiltinSchema(): void
    {
        $factory = new SchemaFactory();

        $schema = $factory->getBuiltinSchema();

        $xsds = [];

        foreach ($schema->getXsds() as $uri => $xsd) {
            $xsds[basename((new Uri($uri))->getPath())] =
                $xsd->documentElement->targetNamespace;
        }

        $this->assertSame(
            [
                'XMLSchema.xsd' => Schema::XSD_NS,
                'xml.xsd' => Schema::XML_NS
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame($schema, $factory->getBuiltinSchema());
    }
}
