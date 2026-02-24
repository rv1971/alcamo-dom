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
            ],
            [
                "data:,%3C%3Fxml%20version='1.0'%3F%3E%3Cschema%20"
                    . "xmlns='http://www.w3.org/2001/XMLSchema'%20"
                    . "targetNamespace='tag:rv1971%40web.de,2021:alcamo:ns:base%23'%3E"
                    . "%3CsimpleType%20name='NumericString'%20"
                    . "xml:id='NumericString'%3E%3Crestriction%20"
                    . "base='string'%3E%3Cpattern%20value='%5Cd+'/%3E"
                    . "%3C/restriction%3E%3C/simpleType%3E%3C/schema%3E"
                    . "#NumericString",
                AtomicType::class,
                'tag:rv1971@web.de,2021:alcamo:ns:base#'
            ]
        ];
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
