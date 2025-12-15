<?php

namespace alcamo\dom\schema;

use alcamo\dom\Document;
use alcamo\dom\decorated\DocumentFactory;
use alcamo\dom\schema\component\{AtomicType, EnumerationType, UnionType};
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
                'xml.xsd' => Schema::XML_NS,
                'xhtml-datatypes-1.xsd' => Schema::XH11D_NS
            ],
            $xsds
        );

        /* Test caching. */
        $this->assertSame($schema, $factory->getBuiltinSchema());
    }

    /**
     * @dataProvider createTypeFromUriProvider
     */
    public function testCreateTypeFromUri(
        $uri,
        $expectedClass,
        $expectedNsName
    ): void {
        $factory = new SchemaFactory(
            new DocumentFactory(
                (new FileUriFactory())->create(
                    __DIR__ . DIRECTORY_SEPARATOR
                        . '..' . DIRECTORY_SEPARATOR
                        . '..' . DIRECTORY_SEPARATOR
                )
            )
        );

        $type = $factory->createTypeFromUri($uri);

        $this->assertInstanceOf($expectedClass, $type);

        $this->assertSame($expectedNsName, $type->getXName()->getNsName());

        $this->assertSame($type, $factory->createTypeFromUri($uri));
    }

    public function createTypeFromUriProvider(): array
    {
        return [
            [
                'xsd/XMLSchema.xsd#formChoice',
                EnumerationType::class,
                'http://www.w3.org/2001/XMLSchema'
            ],
            [
                'xsd/XMLSchema.xsd#derivationSet',
                UnionType::class,
                'http://www.w3.org/2001/XMLSchema'
            ],
            [
                'http://www.w3.org/2001/XMLSchema#boolean',
                AtomicType::class,
                'http://www.w3.org/2001/XMLSchema'
            ],
            [
                'tests/foo.xsd#UpperString',
                AtomicType::class,
                'http://foo.example.org'
            ]
        ];
    }
}
