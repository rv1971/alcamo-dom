<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use alcamo\exception\{
    AbsoluteUriNeeded,
    DataValidationFailed,
    FileLoadFailed,
    Uninitialized
};
use alcamo\ietf\Uri;

class DocumentTest extends TestCase
{
    /**
     * @dataProvider contentProvider
     */
    public function testContent($doc, $expectedUri)
    {
        $this->assertInstanceOf(Element::class, $doc->documentElement);

        $this->assertInstanceOf(
            Attr::class,
            $doc->documentElement->getAttributeNode('qux')
        );

        $this->assertInstanceOf(
            Text::class,
            $doc->documentElement->firstChild->firstChild
        );

        $this->assertSame($expectedUri, $doc->documentURI);

        $this->assertSame(
            '42-43',
            (string)$doc->documentElement->getAttributeNode('qux')
        );

        $this->assertSame(true, isset($doc['x']));

        $this->assertSame(false, isset($doc['xx']));

        $this->assertSame('bar', $doc['x']->tagName);

        $this->assertSame('At eos', (string)$doc['a']->firstChild);

        $this->assertSame('baz', $doc->query('//foo:baz')[0]->tagName);

        $this->assertSame(
            'Lorem ipsum',
            (string)$doc->query('//rdfs:comment/text()')[0]
        );

        $this->assertSame(22, (int)$doc->evaluate('count(//foo:baz)'));

        $this->assertInstanceOf(
            \XSLTProcessor::class,
            $doc->getXsltProcessor()
        );
    }

    public function contentProvider()
    {
        $doc1 =
            Document::newFromUrl(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $doc1->getXPath()->registerNamespace('foo', 'http://foo.example.org');

        chdir(__DIR__);

        $doc2 = Document::newFromXmlText(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml')
        );

        $doc2->getXPath()->registerNamespace('foo', 'http://foo.example.org');

        return [
            'from-url' => [
                $doc1,
                (string)Uri::newFromFilesystemPath(
                    __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml',
                    false
                )
            ],
            'from-xml' => [
                $doc2,
                (string)Uri::newFromFilesystemPath(
                    __DIR__ . DIRECTORY_SEPARATOR,
                    false
                )
            ]
        ];
    }

    public function testXName()
    {
        $doc = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $foo = $doc->documentElement;

        $this->assertSame(
            'http://foo.example.org foo',
            (string)$foo->getXName()
        );

        $this->assertSame(
            'qux',
            (string)$foo->getAttributeNode('qux')->getXName()
        );

        $this->assertSame(
            Document::NS['xml'] . ' lang',
            (string)$foo
                ->getAttributeNodeNS(Document::NS['xml'], 'lang')
                ->getXName()
        );
    }

    public function testXPathException()
    {
        $doc = new Document();

        $this->expectException(Uninitialized::class);
        $this->expectExceptionMessage(
            'Attempt to access uninitialized ' . Document::class
        );

        $doc->getXPath();
    }

    public function testXsltProcessorException()
    {
        $doc = new Document();

        $this->expectException(Uninitialized::class);
        $this->expectExceptionMessage(
            'Attempt to access uninitialized ' . Document::class
        );

        $doc->getXsltProcessor();
    }

    public function testScope()
    {
        $elem1 = (Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        ))->documentElement;

        $this->assertInstanceOf(Element::class, $elem1);

        /** If the Document object goes out of scope, it is destroyed, and the
         *  `$ownerDocument` property returns the underlying base object
         *  only. */
        $this->assertInstanceOf(\DOMDocument::class, $elem1->ownerDocument);

        $this->assertFalse($elem1->ownerDocument instanceof Document);

        $elem2 = (Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        ))->conserve()->documentElement;

        $this->assertInstanceOf(Element::class, $elem2);

        /** If the Document object is still referenced somewhere, the
         *  `$ownerDocument` property returns the complete derived object. */
        $this->assertInstanceOf(Document::class, $elem2->ownerDocument);

        $elem2->ownerDocument->unconserve();

        $this->assertInstanceOf(\DOMDocument::class, $elem2->ownerDocument);
        $this->assertFalse($elem2->ownerDocument instanceof Document);
    }

    public function testNoSchemaLocation()
    {
        $baz = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'qux.xml'
        )->validate();

        $this->assertSame([], $baz->getSchemaLocations());
    }

    public function testNoNsValidate()
    {
        $bar = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'
        )->validate();

        $this->expectException(DataValidationFailed::class);

        $bar->validateWithSchema(__DIR__ . DIRECTORY_SEPARATOR . 'baz.xsd');
    }

    public function testValidate()
    {
        $bar = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        )->validate();

        $this->assertEquals(
            [
                'http://foo.example.org',
                'http://www.w3.org/2000/01/rdf-schema#'
            ],
            array_keys($bar->getSchemaLocations())
        );
    }

    public function testNoNsValidateException()
    {
        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml'
        );

        $this->expectException(DataValidationFailed::class);

        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'bar-invalid.xml'
        );
    }

    public function testValidateException()
    {
        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->expectException(DataValidationFailed::class);

        ValidatedDocument::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo-invalid.xml'
        );
    }
}
