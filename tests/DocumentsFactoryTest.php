<?php

namespace alcamo\dom;

use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentsFactoryTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testProps(): void
    {
        $documentFactory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
        );

        $documentsFactory = new DocumentsFactory($documentFactory);

        $this->assertSame(
            $documentFactory,
            $documentsFactory->getDocumentFactory()
        );

        $this->assertInstanceOf(
            DocumentFactory::class,
            (new DocumentsFactory())->getDocumentFactory()
        );
    }

    public function testCreateFromUris(): void
    {
        $documentFactory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
        );

        $documentsFactory = new DocumentsFactory($documentFactory);

        $documents = $documentsFactory->createFromUris(
            [ 'bar' => 'bar.xml', 'foo' => 'foo.xml', 'empty-baz.xml' ]
        );

        $this->assertSame(3, count($documents));

        $this->assertSame([ 'bar', 'foo', 'BAZ' ], $documents->getKeys());
    }

    public function testCreateFromGlob(): void
    {
        $documentsFactory = new DocumentsFactory();

        $documents1 = $documentsFactory->createFromGlob(
            __DIR__ . DIRECTORY_SEPARATOR . '???.xml',
            0
        );

        $this->assertSame(2, count($documents1));

        $this->assertSame([ 'bar.xml', 'foo.xml' ], $documents1->getKeys());

        $documents2 = $documentsFactory->createFromGlob('{b,f}??.xml', 0);

        $this->assertSame(2, count($documents1));

        $keys = $documents1->getKeys();

        sort($keys);

        $this->assertSame([ 'bar.xml', 'foo.xml' ], $keys);
    }
}
