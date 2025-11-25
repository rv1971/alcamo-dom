<?php

namespace alcamo\dom;

use alcamo\exception\{AbsoluteUriNeeded, InvalidType, ReadonlyViolation};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class MyCachedDocument extends Document
{
}

class DocumentCacheTest extends TestCase
{
    public function testBasics(): void
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        /* Does not use cache because URI is relative. */
        $doc1 = (new MyDocumentFactory())->createFromUrl(
            __DIR__ . DIRECTORY_SEPARATOR . 'empty-foo.xml'
        );

        $this->assertSame(0, count(DocumentCache::getInstance()));

        /* Does not use cache because explicitely deactivated. */
        $doc2 = $factory->createFromUrl('empty-foo.xml', null, false);

        $this->assertSame(0, count(DocumentCache::getInstance()));

        /* Does use cache. */
        $doc3 = $factory->createFromUrl('empty-foo.xml');

        $this->assertSame(1, count(DocumentCache::getInstance()));

        /* Does use cache. */
        $doc4 = $factory->createFromUrl('empty-foo.xml');

        $this->assertSame($doc3, $doc4);

        /* Does not use cache because explicitely deactivated. */
        $doc5 = $factory->createFromUrl('empty-foo.xml', null, false);

        $this->assertNotSame($doc3, $doc5);

        /* Does use cached document because URL is normalized */
        $doc6 = $factory->createFromUrl(
            '..' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR
                . 'empty-foo.xml'
        );

        $this->assertSame($doc3, $doc6);

        /* Still one document cached since it is always the same. */
        $this->assertSame(1, count(DocumentCache::getInstance()));
    }

    public function testAbsoluteUriNeededException()
    {
        $this->expectException(AbsoluteUriNeeded::class);
        $this->expectExceptionMessage(
            'Relative URI <alcamo\uri\Uri>"empty-foo.xml" given '
                . 'where absolute URI is needed'
        );

        (new MyDocumentFactory())->createFromUrl('empty-foo.xml', null, true);
    }

    public function testReadonlyViolationException()
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        $doc1 = $factory->createFromUrl('empty-foo.xml');

        $this->expectException(ReadonlyViolation::class);
        $this->expectExceptionMessage(
            'Attempt to modify readonly object <alcamo\dom\DocumentCache> '
                . 'in method add(); attempt to replace cache entry "file://'
        );

        $doc2 = $factory->createFromUrl('empty-bar.xml');

        $doc2->documentURI = $doc1->documentURI;

        DocumentCache::getInstance()->add($doc2);
    }

    public function testInvalidTypeException()
    {
        $factory = new MyDocumentFactory(
            (new FileUriFactory())->create(__DIR__ . DIRECTORY_SEPARATOR)
        );

        $factory->createFromUrl('empty-foo.xml');

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "alcamo\dom\Document", expected one of ["alcamo\dom\MyCachedDocument"]'
        );

        $factory->createFromUrl('empty-foo.xml', MyCachedDocument::class);
    }
}
