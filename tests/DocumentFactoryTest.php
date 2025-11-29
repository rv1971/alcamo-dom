<?php

namespace alcamo\dom;

use alcamo\dom\decorated\Document as Xsd;
use alcamo\exception\{AbsoluteUriNeeded, DataValidationFailed, InvalidType};
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
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

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
    public function testCreate($uri, $class, $expectedNamespace): void
    {
        $baseUri = (new FileUriFactory())->create(self::DATA_DIR);
        $loadFlags = Document::XINCLUDE_AFTER_LOAD;
        $libXmlOptions = LIBXML_NOBLANKS;

        $factory = new DocumentFactory($baseUri, $loadFlags, $libXmlOptions);

        $doc1 = $factory->createFromUri($uri, $class, false);

        $doc2 = $factory->createFromUri($uri, $class, false, 0, LIBXML_COMPACT);

        $doc3 = $factory->createFromXmlText(
            file_get_contents($factory->resolveUri($uri)),
            $class,
            null,
            null,
            $uri
        );

        $doc4 = $factory->createFromXmlText(
            file_get_contents($factory->resolveUri($uri)),
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

        $this->assertSame($factory, $doc1->getDocumentFactory());
        $this->assertSame($factory, $doc2->getDocumentFactory());
        $this->assertSame($factory, $doc3->getDocumentFactory());
        $this->assertSame($factory, $doc4->getDocumentFactory());

        $this->assertSame($libXmlOptions, $doc1->getLibxmlOptions());
        $this->assertSame(LIBXML_COMPACT, $doc2->getLibxmlOptions());
        $this->assertSame($libXmlOptions, $doc3->getLibxmlOptions());
        $this->assertSame(LIBXML_COMPACT, $doc4->getLibxmlOptions());

        $this->assertSame(
            (string)UriResolver::resolve($baseUri, new Uri($uri)),
            $doc1->documentURI
        );

        $this->assertSame(
            (string)UriResolver::resolve($baseUri, new Uri($uri)),
            $doc2->documentURI
        );

        $this->assertSame(
            (string)UriResolver::resolve($baseUri, new Uri($uri)),
            $doc3->documentURI
        );

        $this->assertSame(
            (string)(new FileUriFactory())
                ->create(getcwd() . DIRECTORY_SEPARATOR),
            $doc4->documentURI
        );

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

    public function testCreateElement(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $bar = $factory->createFromUri('foo.xml#bar');

        $this->assertSame('bar', $bar->localName);
        $this->assertInstanceOf(Document::class, $bar->ownerDocument);

        /* $bar2 document is taken from cache */
        $bar2 = $factory->createFromUri('foo.xml#bar');

        $this->assertSame($bar, $bar2);

        $baz = $factory->createFromUri('foo.xml#baz');

        $this->assertNull($baz);

        chdir(__DIR__);

        /* $bar3 document is not taken from cache */
        $bar3 = (new DocumentFactory())->createFromUri('foo.xml#bar');

        $this->assertSame('bar', $bar->localName);
        $this->assertFalse($bar === $bar3);
        $this->assertInstanceOf(\DOMDocument::class, $bar->ownerDocument);
        $this->assertFalse($bar3->ownerDocument instanceof Document);
    }

    /**
     * @dataProvider getClassForDocumentProvider
     */
    public function testGetClassForDocument($uri, $expectedClass): void
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $doc = $factory->createFromUri($uri, null, false);

        $this->assertSame($expectedClass, get_class($doc));
    }

    public function getClassForDocumentProvider(): array
    {
        return [
            [ self::XSD_DIR . 'xml.xsd', Xsd::class ],
            [ 'foo.xml', Document::class ],
            [ 'empty-bar.xml', BarDocument::class ],
            [ 'empty-baz.xml', BazDocument::class ]
        ];
    }

    public function testAbsoluteUriNeededException()
    {
        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI <alcamo\uri\Uri>"foo.xml" given '
                . 'where absolute URI is needed'
        );

        (new DocumentFactory())->createFromUri('foo.xml', null, true);
    }

    public function testInvalidTypeException()
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $factory->createFromUri('foo.xml');

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "alcamo\dom\Document", expected one of ["alcamo\dom\MyCachedDocument"]'
        );

        $factory->createFromUri('foo.xml', MyCachedDocument::class);
    }

    public function testValidateAfterLoad(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
            Document::VALIDATE_AFTER_LOAD
        );

        /* Valid document. */
        $this->assertInstanceOf(
            Document::class,
            $factory->createFromUri('bar.xml')
        );

        /* Invalid document. */
        $this->expectException(DataValidationFailed::class);

        $factory->createFromUri('invalid-bar-2.xml');
    }


    public function testXInclude(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR)
        );

        $bar1 = $factory->createFromUri('bar-includer.xml', null, false);

        $this->assertSame(
            'include',
            $bar1->documentElement->firstChild->localName
        );

        $bar2 = $factory->createFromUri(
            'bar-includer.xml',
            null,
            false,
            Document::XINCLUDE_AFTER_LOAD | Document::VALIDATE_AFTER_XINCLUDE
        );

        $this->assertSame(
            'baz',
            $bar2->documentElement->firstChild->localName
        );

        /* Document valid only after xinclude. */
        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            "Element '{http://www.w3.org/2001/XInclude}include': "
                . "This element is not expected"
        );

        $bar3 = $factory->createFromUri(
            'bar-includer.xml',
            null,
            false,
            Document::VALIDATE_AFTER_LOAD
        );
    }

    public function testXIncludeInvalid(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
            Document::XINCLUDE_AFTER_LOAD | Document::VALIDATE_AFTER_XINCLUDE
        );

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            "Element '{http://foo.example.org}foo': "
                . "This element is not expected"
        );

        $factory->createFromUri('invalid-bar-includer.xml', null, false);
    }
}
