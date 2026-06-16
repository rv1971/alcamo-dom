<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;
use alcamo\dom\decorated\DocumentFactory;
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
                'foo.xsd' => 'http://foo.example.org',
                'xhtml-datatypes-1.xsd' => Schema::XH11D_NS
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame($schema, $factory->createFromDocument($doc));
    }

    public function testCreateXsds(): void
    {
        $documentFactory = new DocumentFactory(
            (new FileUriFactory())->create(
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
            )
        );

        $factory = new SchemaFactory();

        $schema = $factory->createFromXsds(
            [
                $documentFactory->createFromUri('no-ns-foo.xsd'),
                $documentFactory->createFromUri('bar.xsd')
            ]
        );

        $xsds = [];

        foreach ($schema->getXsds() as $uri => $xsd) {
            $xsds[basename((new Uri($uri))->getPath())] =
                $xsd->documentElement->targetNamespace;
        }

        $this->assertSame(
            [
                'XMLSchema.xsd' => Schema::XSD_NS,
                'xml.xsd' => Schema::XML_NS,
                'bar.xsd' => 'https://bar.example.com',
                'aux-bar.xsd' => 'https://bar.example.com',
                'foo.xsd' => 'http://foo.example.org',
                'xhtml-datatypes-1.xsd' => Schema::XH11D_NS,
                'no-ns-foo.xsd' => null
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame(
            $schema,
            $factory->createFromXsds(
                [
                    $documentFactory->createFromUri('no-ns-foo.xsd'),
                    $documentFactory->createFromUri('bar.xsd')
                ]
            )
        );
    }

    public function testGetMainSchema(): void
    {
        $factory = new SchemaFactory();

        $schema = $factory->getMainSchema();

        $xsds = [];

        foreach ($schema->getXsds() as $uri => $xsd) {
            $xsds[basename((new Uri($uri))->getPath())] =
                $xsd->documentElement->targetNamespace;
        }

        $this->assertSame(
            [
                'XMLSchema.xsd' => Schema::XSD_NS,
                'xml.xsd' => Schema::XML_NS,
                'xhtml-datatypes-1.xsd' => Schema::XH11D_NS
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame($schema, $factory->getMainSchema());
    }

    public function testCreateFromDirectory(): void
    {
        $factory = new SchemaFactory();

        $schema = $factory->createFromDirectory(__DIR__);

        $paths = [];

        $baseUriLen = strlen(
            (new FileUriFactory())->create(dirname(dirname(__DIR__)))
        ) + 1;

        foreach ($schema->getTopXsds() as $uri => $xsd) {
            $paths[] = substr($uri, $baseUriLen);
        }

        $this->assertSame(
            [
                'xsd/XMLSchema.xsd',
                'xsd/xml.xsd',
                'tests/schema/qux.2.xsd',
                'xsd/xhtml-datatypes-1.xsd',
                'tests/schema/qux.1.xsd',
                'tests/schema/component/foo.xsd',
                'tests/schema/bar.xsd'
            ],
            $paths
        );
    }
}
