<?php

namespace alcamo\dom;

use GuzzleHttp\Psr7\UriResolver;
use PHPUnit\Framework\TestCase;
use alcamo\exception\{
    AbsoluteUriNeeded,
    FileLoadFailed,
    Uninitialized
};
use alcamo\dom\xsl\Document as Stylesheet;
use alcamo\uri\FileUriFactory;

class ReparsedDocument extends Document
{
    public const LOAD_FLAGS =
        self::XINCLUDE_AFTER_LOAD | self::FORMAT_AND_REPARSE;
}

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

        $this->assertSame(24, (int)$doc->evaluate('count(//foo:baz)'));

        $this->assertInstanceOf(Stylesheet::class, $doc->getXsltStylesheet());
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

        $doc3Url = __DIR__ . DIRECTORY_SEPARATOR . 'bar.xml';

        $doc3 = Document::newFromXmlText(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'),
            null,
            null,
            null,
            $doc3Url
        );

        $doc3->getXPath()->registerNamespace('foo', 'http://foo.example.org');

        return [
            'from-url' => [
                $doc1,
                (new FileUriFactory())->fsPath2FileUrlPath(
                    __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
                )
            ],
            'from-xml' => [
                $doc2,
                (new FileUriFactory())->fsPath2FileUrlPath(
                    __DIR__ . DIRECTORY_SEPARATOR
                )
            ],
            'from-xml-with-url' => [ $doc3, $doc3Url ]
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
            Document::XML_NS . ' lang',
            (string)$foo
                ->getAttributeNodeNS(Document::XML_NS, 'lang')
                ->getXName()
        );
    }

    public function testXPathException()
    {
        $doc = new Document();

        $this->expectException(Uninitialized::class);
        $this->expectExceptionMessage(
            'Attempt to access uninitialized object <' . Document::class . '>'
        );

        $doc->getXPath();
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

    public function testXinclude()
    {
        $quux = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'quux.xml',
            null,
            Document::XINCLUDE_AFTER_LOAD | Document::VALIDATE_AFTER_XINCLUDE
        );

        $this->assertEquals(
            'corge',
            $quux->documentElement->firstChild->tagName
        );
    }

    public function testReparse()
    {
        $url = __DIR__ . DIRECTORY_SEPARATOR . 'quux.xml';

        $quux = ReparsedDocument::newFromUrl($url);

        $corge = $quux->documentElement->firstChild;

        $this->assertEquals('corge', $corge->tagName);

        $this->assertEquals(3, $corge->getLineNo());

        $this->assertEquals($url, $quux->documentURI);
    }

    public function testStripXsdDocumentation()
    {
        $doc1 = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'fooWithDocumenation.xml'
        );

        $this->assertSame(
            22,
            $doc1->stripXsdDocumentation()
                ->getElementsByTagName('baz')[0]->getLineNo()
        );

        $doc2 = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'fooWithDocumenation.xml'
        );

        $doc2->formatOutput = true;

        $this->assertSame(
            7,
            $doc2->stripXsdDocumentation(true)
                ->getElementsByTagName('baz')[0]->getLineNo()
        );

        $this->assertSame(
            file_get_contents('fooWithDocumenation.stripped.xml'),
            $doc2->saveXML()
        );
    }

    public function testIteration()
    {
        $expectedTagNames = [
            'rdfs:comment',
            'bar',
            'corge',
            'xsd:annotation',
            'qux'
        ];

        foreach (
            Document::newFromUrl(
                __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
            ) as $pos => $element
        ) {
            $this->assertSame($expectedTagNames[$pos], $element->tagName);
        }
    }

    public function testClone()
    {
        $doc1 = Document::newFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $doc2 = clone $doc1;

        $this->assertNotSame($doc1->getXPath(), $doc2->getXPath());

        $this->assertSame($doc1, $doc1->getXPath()->document);

        $this->assertSame($doc2, $doc2->getXPath()->document);
    }
}
