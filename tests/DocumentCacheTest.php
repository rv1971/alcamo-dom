<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, ReadonlyViolation};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class MyCachedDocument extends Document
{
}

class DocumentCacheTest extends TestCase
{
    public function testBasics(): void
    {
        DocumentCache::getInstance()->init();

        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        /* Does not use cache because URI is relative. */
        $doc1 = (new MyDocumentFactory())->createFromUri(
            __DIR__ . DIRECTORY_SEPARATOR . 'foo.xml'
        );

        $this->assertSame(0, count(DocumentCache::getInstance()));

        /* Does not use cache because explicitely deactivated. */
        $doc2 = $factory->createFromUri('foo.xml', null, false);

        $this->assertSame(0, count(DocumentCache::getInstance()));

        /* Does use cache. */
        $doc3 = $factory->createFromUri('foo.xml');

        $this->assertSame(1, count(DocumentCache::getInstance()));

        /* Does use cache. */
        $doc4 = $factory->createFromUri('foo.xml');

        $this->assertSame($doc3, $doc4);

        /* Does not use cache because explicitely deactivated. */
        $doc5 = $factory->createFromUri('foo.xml', null, false);

        $this->assertNotSame($doc3, $doc5);

        /* Does use cached document because URI is normalized */
        $doc6 = $factory->createFromUri(
            '..' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR
                . 'foo.xml'
        );

        $this->assertSame($doc3, $doc6);

        /* Still one document cached since it is always the same. */
        $this->assertSame(1, count(DocumentCache::getInstance()));

        DocumentCache::getInstance()->init();

        $this->assertSame(0, count(DocumentCache::getInstance()));

        $fooXsdText =
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $doc7 = Document::newFromXmlText($fooXsdText);

        $this->assertTrue(DocumentCache::getInstance()->add($doc7));

        $doc8 = Document::newFromXmlText($fooXsdText);

        $this->assertSame(
            $doc7,
            DocumentCache::getInstance()[$doc8->getIdentifierUri()]
        );
    }

    public function testAbsoluteUriNeededException()
    {
        $fooDoc =
            Document::newFromUri(__DIR__ . DIRECTORY_SEPARATOR . 'foo.xml');

        $fooDoc->documentURI = 'foo.xml';

        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI "foo.xml" given where absolute URI is needed'
        );

        DocumentCache::getInstance()->add($fooDoc);
    }

    public function testReadonlyViolationException()
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        $doc1 = $factory->createFromUri('foo.xml', null, true);

        $this->expectException(ReadonlyViolation::class);
        $this->expectExceptionMessage(
            'Attempt to modify readonly object <alcamo\dom\DocumentCache> '
                . 'in method add(); attempt to replace cache entry "file://'
        );

        $doc2 = clone $doc1;

        DocumentCache::getInstance()->add($doc2);
    }
}
