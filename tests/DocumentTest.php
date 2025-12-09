<?php

namespace alcamo\dom;

use alcamo\exception\{
    FileLoadFailed,
    SyntaxError,
    Uninitialized
};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testClone(): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $doc1 = Document::newFromUri($fooUri);

        $doc2 = clone $doc1;

        $this->assertSame($doc1->getXPath(), $doc1->getXPath());

        $this->assertFalse($doc1->getXPath() === $doc2->getXPath());

        $doc2->removeChild(
            $doc2->query('/processing-instruction("xml-stylesheet")')[0]
        );

        $this->assertNull($doc2->getXsltStylesheet());
    }

    public function testGetDocumentFactory(): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $fooDoc = Document::newFromUri($fooUri, null, 4096, 0);

        $documentFactory = $fooDoc->getDocumentFactory();

        $this->assertInstanceOf(DocumentFactory::class, $documentFactory);
        $this->assertSame('', (string)$documentFactory->getBaseUri());
        $this->assertSame(4096, $documentFactory->getLoadFlags());
        $this->assertSame(0, $documentFactory->getLibxmlOptions());

        $fooDoc2 = $documentFactory->createFromUri($fooUri, null, false);

        $firstChild = $fooDoc2->documentElement->firstChild;

        $this->assertInstanceOf(Text::class, $firstChild);
        $this->assertSame("\n  ", (string)$firstChild);
    }

    public function testGetConserve(): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $foo1 = Document::newFromUri($fooUri)->documentElement;

        $this->assertInstanceOf(\DOMDocument::class, $foo1->ownerDocument);
        $this->assertFalse($foo1 instanceof Document);

        $foo2 = Document::newFromUri($fooUri)->conserve()->documentElement;

        $this->assertInstanceOf(Document::class, $foo2->ownerDocument);

        $foo2->ownerDocument->unconserve();

        $this->assertInstanceOf(\DOMDocument::class, $foo2->ownerDocument);
        $this->assertFalse($foo2 instanceof Document);
    }

    public function testGetIterator(): void
    {
        /* This also tests class ChildElementsIterator. */

        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $fooDoc = Document::newFromUri($fooUri);

        $data = [];

        foreach ($fooDoc as $i => $element) {
            $data[$i] = $element->localName;
        }

        $this->assertSame([ 0 => 'bar' ], $data);
    }

    /**
     * @dataProvider arrayAccessProvider
     */
    public function testArrayAccess($key, $expectedLocalName): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $fooDoc = Document::newFromUri($fooUri);

        if (isset($expectedLocalName)) {
            $this->assertTrue(isset($fooDoc[$key]));
            $this->assertSame($expectedLocalName, $fooDoc[$key]->localName);
        } else {
            $this->assertFalse(isset($fooDoc[$key]));
            $this->assertNull($fooDoc[$key]);
        }
    }

    public function arrayAccessProvider(): array
    {
        return [
            [ 'bar', 'bar' ],
            [ 'baz', null ]
        ];
    }

    public function xPathTest(): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $fooDoc = Document::newFromUri($fooUri);

        $this->assertInstanceOf(
            ProcessingInstruction::class,
            $fooDoc->query('node()')[2]->nodeType
        );

        $this->assertSame(2, $fooDoc->evaluate('count("//comment()")'));
    }

    public function getXsltStylesheetTest(): void
    {
        $fooUri = (new FileUriFactory())->create(self::DATA_DIR . 'foo.xml');

        $fooDoc = Document::newFromUri($fooUri);

        $xslt = $fooDoc->getXsltStylesheet();

        $this->assertSame($xslt, $fooDoc->getXsltStylesheet());

        $this->assertInstanceOf(Document::class, $xslt);

        $this->assertSame('stylesheet', $xslt->documentElement->localName());

        $barUri = (new FileUriFactory())->create(self::DATA_DIR . 'bar.xml');

        $barDoc = Document::newFromUri($barUri);

        $this->assertNull($barDoc->getXsltStylesheet());
    }

    public function testLoadUriException1(): void
    {
        $this->expectException(FileLoadFailed::class);
        $this->expectExceptionMessage(
            'Failed to load "none.xml"; DOMDocument::load(): I/O warning : '
                . 'failed to load external entity'
        );

        Document::newFromUri('none.xml');
    }

    public function testLoadUriException2(): void
    {
        $illFormedUri =
            (new FileUriFactory())->create(self::DATA_DIR . 'ill-formed.xml');

        $this->expectException(FileLoadFailed::class);
        $this->expectExceptionMessage(
            "DOMDocument::load(): Start tag expected, '<' not found"
        );

        Document::newFromUri($illFormedUri);
    }

    public function testLoadXmlException(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage(
            "DOMDocument::loadXML(): Start tag expected, '<' not found"
        );

        Document::newFromXmlText('ILL-FORMED');
    }

    public function testGetXPathException(): void
    {
        $doc = new Document();

        $this->expectException(Uninitialized::class);
        $this->expectExceptionMessage(
            'Attempt to access uninitialized object <alcamo\dom\Document>'
        );

        $doc->getXPath();
    }

    public function testGetXsltStylesheetException(): void
    {
        $doc = new Document();

        $this->expectException(Uninitialized::class);
        $this->expectExceptionMessage(
            'Attempt to access uninitialized object <alcamo\dom\Document>'
        );

        $doc->getXsltStylesheet();
    }
}
