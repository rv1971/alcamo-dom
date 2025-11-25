<?php

namespace alcamo\dom;

use alcamo\dom\decorated\Document as Xsd;
use alcamo\uri\{FileUriFactory, Uri};
use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;

class BarDocument extends Document
{
}

class BazDocument extends Document
{
}

class MyDocumentFactory extends DocumentFactory
{
    public const DC_IDENTIFIER_PREFIX_TO_CLASS = [
        'BAZ' => BazDocument::class
    ];

    public const NS_NAME_TO_CLASS = [
        'https://bar.example.com' => BarDocument::class
    ];
}

class DocumentFactoryTest extends TestCase
{
    public const XSD_DIR =
        '..' . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR;

    /**
     * @dataProvider propsProvider
     */
    public function testProps($baseUri, $loadFlags, $libXmlOptions): void
    {
        $factory = new DocumentFactory($baseUri, $loadFlags, $libXmlOptions);

        $this->assertSame((string)$baseUri, (string)$factory->getBaseUri());

        $this->assertSame($loadFlags, $factory->getLoadFlags());

        $this->assertSame($libXmlOptions, $factory->getLibxmlOptions());
    }

    public function propsProvider(): array
    {
        return [
            [ null, null, null ],
            [ 'http://www.example.org/', LIBXML_COMPACT, 0 ],
            [ new Uri('https://www.example.info/'), 42, 43 ]
        ];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate($url, $class, $expectedNamespace): void
    {
        $baseUrl =
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR);
        $loadFlags = Document::XINCLUDE_AFTER_LOAD;
        $libXmlOptions = LIBXML_NOBLANKS;

        $factory = new DocumentFactory($baseUrl, $loadFlags, $libXmlOptions);

        $doc1 = $factory->createFromUrl($url, $class, false);

        $doc2 = $factory->createFromUrl($url, $class, false, 0, LIBXML_COMPACT);

        $doc3 = $factory->createFromXmlText(
            file_get_contents($url),
            $class,
            null,
            null,
            $url
        );

        $doc4 = $factory->createFromXmlText(
            file_get_contents($url),
            $class,
            0,
            LIBXML_COMPACT
        );

        if (isset($class)) {
            $this->assertSame($class, get_class($doc1));
            $this->assertSame($class, get_class($doc2));
            $this->assertSame($class, get_class($doc3));
            $this->assertSame($class, get_class($doc4));
        }

        $this->assertSame($libXmlOptions, $doc1->getLibxmlOptions());
        $this->assertSame(LIBXML_COMPACT, $doc2->getLibxmlOptions());
        $this->assertSame($libXmlOptions, $doc3->getLibxmlOptions());
        $this->assertSame(LIBXML_COMPACT, $doc4->getLibxmlOptions());

        $this->assertSame(
            (string)UriResolver::resolve($baseUrl, new Uri($url)),
            $doc1->documentURI
        );

        $this->assertSame(
            (string)UriResolver::resolve($baseUrl, new Uri($url)),
            $doc2->documentURI
        );

        $this->assertSame(
            (string)UriResolver::resolve($baseUrl, new Uri($url)),
            $doc3->documentURI
        );

        $this->assertSame((string)$baseUrl, $doc4->documentURI);

        $this->assertSame(
            $expectedNamespace,
            $doc1->documentElement->namespaceURI
        );

        $this->assertSame(
            $expectedNamespace,
            $doc2->documentElement->namespaceURI
        );

        $this->assertSame(
            $expectedNamespace,
            $doc3->documentElement->namespaceURI
        );

        $this->assertSame(
            $expectedNamespace,
            $doc4->documentElement->namespaceURI
        );
    }

    public function createProvider(): array
    {
        return [
            [ self::XSD_DIR . 'xml.xsd', null, Document::XSD_NS ],
            [
                self::XSD_DIR . 'XMLSchema.xsd',
                BarDocument::class,
                Document::XSD_NS
            ]
        ];
    }

    /**
     * @dataProvider getClassForDocumentProvider
     */
    public function testGetClassForDocument($url, $expectedClass): void
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        $doc = $factory->createFromUrl($url, null, false);

        $this->assertSame($expectedClass, get_class($doc));
    }

    public function getClassForDocumentProvider(): array
    {
        return [
            [ self::XSD_DIR . 'xml.xsd', Xsd::class ],
            [ 'empty-foo.xml', Document::class ],
            [ 'empty-bar.xml', BarDocument::class ],
            [ 'empty-baz.xml', BazDocument::class ]
        ];
    }
}
