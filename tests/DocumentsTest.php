<?php

namespace alcamo\dom;

use alcamo\exception\{DataValidationFailed, InvalidType};
use alcamo\uri\FileUriFactory;
use PHPUnit\Framework\TestCase;

class DocumentsTest extends TestCase
{
    public const DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR;

    public function testConstruct(): void
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
        );

        $foo = $factory->createFromUri('foo.xml');
        $bar = $factory->createFromUri('bar.xml');
        $baz = $factory->createFromUri('empty-baz.xml');

        /* The duplicate $foo si silently ignored because it is the same
         * document with the same key. */
        $items = [ $foo, 'foo.xml' => $foo, 'my-bar' => $bar, $baz];

        $documents = new Documents($items);

        $this->assertSame(3, count($documents));

        $this->assertSame($foo, $documents['foo.xml']);
        $this->assertSame($bar, $documents['my-bar']);
        $this->assertSame($baz, $documents['BAZ']);
    }

    public function testConstructException1()
    {
        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage(
            'Invalid type "DOMDocument", '
                . 'expected one of ["alcamo\dom\Document"] for key 0'
        );

        new Documents([ new\DOMDocument() ]);
    }

    public function testConstructException2()
    {
        $factory = new DocumentFactory(
            (new FileUriFactory())->create(self::DATA_DIR),
        );

        $foo = $factory->createFromUri('foo.xml');
        $baz = $factory->createFromUri('empty-baz.xml');

        $this->expectException(DataValidationFailed::class);
        $this->expectExceptionMessage(
            'Validation failed for key "BAZ"; '
                . 'two different documents for the same key'
        );

        new Documents([ 'BAZ' => $foo, $baz ]);
    }
}
